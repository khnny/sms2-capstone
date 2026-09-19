<?php
/**
 * Research Implementation & Progress Monitoring Module
 * Database Installation Script
 * 
 * This script installs the progress monitoring tables into crad_db.
 * Safe to run multiple times - checks for existing tables and data.
 */

declare(strict_types=1);

// Require existing CRAD configuration
require_once __DIR__ . '/../config/config.php';

if (PHP_SAPI !== 'cli') {
    // Web-based execution with basic protection
    session_start();
    require_once __DIR__ . '/../../../config/config.php';
    require_once ROOT_PATH . '/includes/authentication.php';
    $isAuthorized = !empty($_SESSION['user_role_key'])
        && (smsRoleAllowedForModule(['crad_officer'], 'crad') || smsCanBypassSystemControls((string) $_SESSION['user_role_key']));
    
    if (!$isAuthorized) {
        http_response_code(403);
        die('Access denied. Admin/CRAD Officer authentication required.');
    }
}

// Get database connection
try {
    $crad = cradDb();
} catch (Throwable $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "========================================\n";
echo "Research Progress Module Installer\n";
echo "========================================\n\n";

// Read and execute schema file
$schemaFile = __DIR__ . '/research_progress_schema.sql';
if (!file_exists($schemaFile)) {
    die("Error: Schema file not found: {$schemaFile}\n");
}

echo "Reading schema file...\n";
$sql = file_get_contents($schemaFile);

// Remove comments and split into individual statements
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/^\s*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    
    // Extract table name for better logging
    $tableName = '';
    if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
        $tableName = $matches[1];
        
        // Check if table exists
        try {
            $check = $crad->query("SHOW TABLES LIKE '{$tableName}'")->fetch();
            if ($check) {
                echo "  [SKIP] Table '{$tableName}' already exists\n";
                $skipCount++;
                continue;
            }
        } catch (Throwable $e) {
            // Continue with creation attempt
        }
    }
    
    try {
        $crad->exec($statement);
        if ($tableName) {
            echo "  [OK] Created table: {$tableName}\n";
        } else {
            echo "  [OK] Executed statement\n";
        }
        $successCount++;
    } catch (PDOException $e) {
        // Check if error is about table already existing
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "  [SKIP] " . ($tableName ?: 'Statement') . " already exists\n";
            $skipCount++;
        } else {
            echo "  [ERROR] " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
}

echo "\n========================================\n";
echo "Installation Summary:\n";
echo "  Created: {$successCount}\n";
echo "  Skipped: {$skipCount}\n";
echo "  Errors:  {$errorCount}\n";
echo "========================================\n\n";

if ($errorCount > 0) {
    echo "WARNING: Some operations failed. Please review errors above.\n\n";
    exit(1);
}

// Initialize default milestones for existing research groups (if any)
echo "Checking for existing research groups without plans...\n\n";

try {
    // Get research groups that don't have a plan yet
    $stmt = $crad->query("
        SELECT rg.id, rg.group_number, rg.group_name, rg.research_title, 
               rg.adviser, rg.academic_year,
               raa.adviser_user_id, raa.adviser_name, raa.adviser_email
        FROM research_groups rg
        LEFT JOIN research_plans rp ON rp.research_group_id = rg.id
        LEFT JOIN research_adviser_assignments raa ON raa.group_number = rg.group_number 
            AND raa.assignment_status = 'Confirmed'
        WHERE rp.id IS NULL 
          AND rg.status = 'Approved'
        ORDER BY rg.date_assigned DESC
    ");
    
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($groups)) {
        echo "No research groups found that need initialization.\n\n";
    } else {
        echo "Found " . count($groups) . " research group(s) without plans.\n";
        echo "Would you like to create default plans and milestones for them? (yes/no): ";
        
        if (PHP_SAPI === 'cli') {
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));
            fclose($handle);
        } else {
            $response = $_GET['auto_create'] ?? 'no';
        }
        
        if (strtolower($response) === 'yes') {
            echo "\nCreating plans and milestones...\n";
            
            foreach ($groups as $group) {
                $groupId = (int) $group['id'];
                $groupNumber = $group['group_number'];
                $groupName = $group['group_name'];
                
                // Create research plan (with duplicate check)
                $checkPlan = $crad->prepare("SELECT id FROM research_plans WHERE research_group_id = ?");
                $checkPlan->execute([$groupId]);
                
                if ($checkPlan->fetch()) {
                    echo "  [SKIP] Plan already exists for {$groupNumber}\n";
                    continue;
                }
                
                $insertPlan = $crad->prepare("
                    INSERT INTO research_plans (
                        research_group_id, group_number, research_title, 
                        adviser_user_id, adviser_name, start_date, status
                    ) VALUES (?, ?, ?, ?, ?, CURDATE(), 'Active')
                ");
                
                $insertPlan->execute([
                    $groupId,
                    $groupNumber,
                    $group['research_title'],
                    $group['adviser_user_id'] ?? null,
                    $group['adviser_name'] ?: $group['adviser']
                ]);
                
                $planId = (int) $crad->lastInsertId();
                echo "  [OK] Created plan for {$groupNumber} - {$groupName}\n";
                
                // Create default milestones (with duplicate prevention)
                $defaultMilestones = [
                    ['name' => 'Chapter 1',          'order' => 1, 'desc' => 'Introduction and Background'],
                    ['name' => 'Chapter 2',          'order' => 2, 'desc' => 'Review of Related Literature'],
                    ['name' => 'Chapter 3',          'order' => 3, 'desc' => 'Methodology'],
                    ['name' => 'Chapter 4',          'order' => 4, 'desc' => 'Results / System Design and Development'],
                    ['name' => 'Chapter 5',          'order' => 5, 'desc' => 'Summary, Conclusions and Recommendations'],
                    ['name' => 'System Development', 'order' => 6, 'desc' => 'System Implementation'],
                    ['name' => 'Testing',            'order' => 7, 'desc' => 'Testing and Quality Assurance'],
                    ['name' => 'Documentation',      'order' => 8, 'desc' => 'Final Documentation and Report'],
                ];
                
                $insertMilestone = $crad->prepare("
                    INSERT IGNORE INTO research_milestones (
                        research_plan_id, milestone_name, milestone_order, description, status
                    ) VALUES (?, ?, ?, ?, 'Not Started')
                ");
                
                $milestoneCount = 0;
                foreach ($defaultMilestones as $milestone) {
                    try {
                        $insertMilestone->execute([
                            $planId,
                            $milestone['name'],
                            $milestone['order'],
                            $milestone['desc']
                        ]);
                        if ($insertMilestone->rowCount() > 0) {
                            $milestoneCount++;
                        }
                    } catch (PDOException $e) {
                        // Skip if duplicate (UNIQUE constraint)
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                    }
                }
                
                echo "      Added {$milestoneCount} milestones\n";
            }
            
            echo "\n[DONE] Initialization complete.\n";
        } else {
            echo "Skipped automatic initialization.\n";
        }
    }
} catch (Throwable $e) {
    echo "Error during initialization: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Installation complete!\n";
echo "========================================\n\n";

if (PHP_SAPI !== 'cli') {
    echo "<br><br><a href='../index.php'>Return to CRAD Module</a>";
}

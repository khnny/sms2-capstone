<?php
/**
 * Shared CRAD title-approval column ensure (QA-002).
 * Cached once per PHP process; skippable via SMS2_SKIP_RUNTIME_SCHEMA=1
 * after migrate.php has applied the unified dump.
 */
declare(strict_types=1);

if (!function_exists('cradEnsureTitleApprovalColumns')) {
    /**
     * Ensure crad_title_approvals has signature/status columns used by the title chain.
     */
    function cradEnsureTitleApprovalColumns(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        if (function_exists('sms2_env')
            && strtolower((string) sms2_env('SMS2_SKIP_RUNTIME_SCHEMA', '0')) === '1'
        ) {
            $done = true;
            return;
        }

        $columns = [
            'adviser_signature_data' => "ALTER TABLE crad_title_approvals ADD COLUMN adviser_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER adviser_remarks",
            'coordinator_status' => "ALTER TABLE crad_title_approvals ADD COLUMN coordinator_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER adviser_signature_data",
            'coordinator_remarks' => "ALTER TABLE crad_title_approvals ADD COLUMN coordinator_remarks TEXT NULL DEFAULT NULL AFTER coordinator_status",
            'coordinator_screening_json' => "ALTER TABLE crad_title_approvals ADD COLUMN coordinator_screening_json TEXT NULL DEFAULT NULL AFTER coordinator_remarks",
            'coordinator_signature_data' => "ALTER TABLE crad_title_approvals ADD COLUMN coordinator_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER coordinator_remarks",
            'coordinator_reviewed_at' => "ALTER TABLE crad_title_approvals ADD COLUMN coordinator_reviewed_at DATETIME NULL DEFAULT NULL AFTER coordinator_signature_data",
            'crad_status' => "ALTER TABLE crad_title_approvals ADD COLUMN crad_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER coordinator_reviewed_at",
            'crad_signature_data' => "ALTER TABLE crad_title_approvals ADD COLUMN crad_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER crad_status",
            'crad_reviewed_at' => "ALTER TABLE crad_title_approvals ADD COLUMN crad_reviewed_at DATETIME NULL DEFAULT NULL AFTER crad_signature_data",
        ];

        foreach ($columns as $column => $sql) {
            try {
                if (!$pdo->query('SHOW COLUMNS FROM crad_title_approvals LIKE ' . $pdo->quote($column))->fetch()) {
                    $pdo->exec($sql);
                }
            } catch (Throwable $e) {
                error_log('CRAD title approval schema ensure failed: ' . $e->getMessage());
            }
        }

        $done = true;
    }
}

<?php
$s = file_get_contents(__DIR__ . '/../modules/crad/file-view.php');
$ok = preg_match('/function cradFileViewAuthorized\b.*?^}/ms', $s, $m);
echo $ok ? ('MATCH len=' . strlen($m[0]) . "\n") : "NO MATCH\n";
if ($ok) {
    echo substr($m[0], -40) . "\n";
    try {
        eval($m[0]);
        echo "eval ok, exists=" . (function_exists('cradFileViewAuthorized') ? '1' : '0') . "\n";
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE crad_research_adviser_assignments (
            adviser_user_id INT, assignment_status TEXT, proposal_id INT, proposal_number TEXT
        )');
        $pdo->exec('CREATE TABLE crad_research_panel_assignments (
            panel_user_id INT, proposal_id INT, proposal_number TEXT, research_group_id INT
        )');
        $pdo->exec('CREATE TABLE crad_research_groups (
            id INT, proposal_id INT, proposal_number TEXT
        )');
        $prop = ['id' => 42, 'submitted_by_user' => 77, 'proposal_number' => 'P-42'];
        $deny = cradFileViewAuthorized($pdo, $prop, 42, 999999, 'panel');
        echo 'deny=' . ($deny ? '1' : '0') . "\n";
    } catch (Throwable $e) {
        echo 'ERR: ' . $e->getMessage() . "\n";
    }
}

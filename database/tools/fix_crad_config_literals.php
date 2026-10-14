<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
require_once $root . '/config/tables.php';
$map = sms2_table_map()['crad'];
uksort($map, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
$path = $root . '/modules/crad/config/config.php';
$c = (string) file_get_contents($path);
foreach ($map as $old => $new) {
    // Only replace quoted logical names that are not already crad_*
    $c = str_replace("'" . $old . "'", "'" . $new . "'", $c);
    $c = str_replace('"' . $old . '"', '"' . $new . '"', $c);
}
file_put_contents($path, $c);
echo "Updated config.php string literals\n";
echo str_contains($c, "IN ('crad_title_approvals'") ? "OK IN clause\n" : "check IN clause\n";
echo str_contains($c, "'research_groups'") ? "WARN bare research_groups remains\n" : "OK no bare research_groups\n";

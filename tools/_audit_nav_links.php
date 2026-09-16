<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$files = [
    $root . '/includes/sidebar.php',
    $root . '/includes/navigation-context.php',
    $root . '/includes/authentication.php',
    $root . '/includes/grant-review-workflow-urls.php',
];
$hrefs = [];
foreach ($files as $f) {
    if (!is_file($f)) continue;
    $c = file_get_contents($f);
    if (preg_match_all("#BASE_URL\s*\.\s*'(/[^']+\.php[^']*)'#", $c, $m)) {
        foreach ($m[1] as $h) $hrefs[$h] = basename($f);
    }
    if (preg_match_all("#['\"](/modules/[^'\"]+\.php[^'\"]*)['\"]#", $c, $m2)) {
        foreach ($m2[1] as $h) $hrefs[$h] = basename($f);
    }
}
// Also scan crad index / faculty index for menu defs
foreach (glob($root . '/modules/*/index.php') ?: [] as $idx) {
    $c = file_get_contents($idx);
    if (preg_match_all("#['\"](?:pages/)?([a-z0-9_-]+\.php)['\"]#", $c, $m)) {
        $mod = basename(dirname($idx));
        foreach ($m[1] as $page) {
            $hrefs["/modules/{$mod}/pages/{$page}"] = basename($idx);
        }
    }
}

ksort($hrefs);
$missing = [];
$ok = 0;
foreach ($hrefs as $h => $src) {
    $pathOnly = preg_replace('/[?#].*$/', '', $h);
    if (!str_starts_with($pathOnly, '/')) continue;
    $full = $root . str_replace('/', DIRECTORY_SEPARATOR, $pathOnly);
    if (!is_file($full)) {
        $missing[] = [$pathOnly, $src];
    } else {
        $ok++;
    }
}
echo "Checked " . count($hrefs) . " href patterns; OK={$ok}; MISSING=" . count($missing) . PHP_EOL;
foreach ($missing as [$p, $s]) {
    echo "MISSING\t{$p}\tfrom {$s}\n";
}

// Faculty sidebar pages from sidebar.php hardcoded list
$sidebar = file_get_contents($root . '/includes/sidebar.php');
preg_match_all("#/modules/(faculty|student-portal|crad)/pages/([a-z0-9_-]+\.php)#", $sidebar, $sm);
$pairs = array_unique(array_map(fn($i) => $sm[1][$i] . '/' . $sm[2][$i], array_keys($sm[1])));
echo "\nSidebar module page refs: " . count($pairs) . PHP_EOL;
foreach ($pairs as $pair) {
    [$mod, $page] = explode('/', $pair, 2);
    $full = $root . "/modules/{$mod}/pages/{$page}";
    if (!is_file($full)) echo "SIDEBAR_MISSING\t/modules/{$mod}/pages/{$page}\n";
}

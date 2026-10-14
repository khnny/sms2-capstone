<?php
$root = dirname(__DIR__);
$php = (defined('PHP_BINARY') && PHP_BINARY !== '') ? PHP_BINARY : 'php';
$errors = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'sms2_system' . DIRECTORY_SEPARATOR . 'sms2_system' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    exec('"' . $php . '" -l "' . $path . '" 2>&1', $out, $code);
    if ($code !== 0) {
        $errors[] = implode("\n", array_filter($out, static fn(string $line): bool => !str_contains($line, 'No syntax errors detected')));
    }
}
file_put_contents($root . '/tools/php-lint-errors.txt', implode("\n\n", $errors));
echo count($errors) . " error(s)\n";
foreach ($errors as $e) {
    echo $e . "\n";
}

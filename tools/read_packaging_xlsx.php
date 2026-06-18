<?php
/**
 * Dump ops packaging spreadsheet for tier alignment checks.
 * Source: Product packaging material list (2).xlsx (user Downloads).
 * Canonical mapping: _System_Core/specs/2026-06-18_costing-parcel-packaging-session.md
 */
require dirname(__DIR__) . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/Admin/Downloads/Product packaging material list (2).xlsx';
if (!file_exists($path)) {
    fwrite(STDERR, "Not found: $path\n");
    exit(1);
}
$ss = IOFactory::load($path);
foreach ($ss->getSheetNames() as $name) {
    echo "=== $name ===\n";
    $ws = $ss->getSheetByName($name);
    $rows = $ws->toArray(null, true, true, true);
    $n = 0;
    foreach ($rows as $i => $row) {
        $cells = array_values($row);
        $line = implode(' | ', array_map(static fn($v) => trim((string) $v), $cells));
        if (trim($line, " |\t\n\r") === '') {
            continue;
        }
        echo $i . ': ' . $line . "\n";
        if (++$n >= 60) {
            echo "... truncated\n";
            break;
        }
    }
    echo "\n";
}

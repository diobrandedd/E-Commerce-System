<?php

declare(strict_types=1);

/**
 * On-demand impact report before editing a shared function.
 *
 * Usage:
 *   php tools/memory_bank/impact.php honey_warehouse_stock_in
 *   php tools/memory_bank/impact.php honey_warehouse_stock_in --write
 */

require_once __DIR__ . '/Generator.php';

$functionName = $argv[1] ?? '';
if ($functionName === '' || str_starts_with($functionName, '-')) {
    fwrite(STDERR, "Usage: php tools/memory_bank/impact.php <function_name> [--write]\n");
    exit(1);
}

$write = in_array('--write', $argv, true);
$report = MemoryBankGenerator::impactReport($functionName);

echo $report;

if ($write) {
    $impactDir = mb_output_dir() . '/impacts';
    if (!is_dir($impactDir)) {
        mkdir($impactDir, 0755, true);
    }
    $out = $impactDir . '/' . $functionName . '.md';
    mb_write($out, $report);
    echo "\nWritten to " . mb_rel_path($out) . "\n";
}

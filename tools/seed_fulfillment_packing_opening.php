<?php

declare(strict_types=1);

/**
 * Seed opening issued_qty for fulfillment packing staging from CLI.
 * Usage: php tools/seed_fulfillment_packing_opening.php [--dry-run]
 *
 * Reads lines from stdin or a CSV file path as argument:
 *   stock_name,qty
 * Example:
 *   Bubble wrap roll - 17 cm,2
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/fulfillment_packing.php';

$dryRun = in_array('--dry-run', $argv, true);
$file = null;
foreach ($argv as $arg) {
    if ($arg !== '--dry-run' && !str_starts_with($arg, '-') && $arg !== $argv[0]) {
        $file = $arg;
        break;
    }
}

$pdo = rts_pdo();
if (!fulfillment_packing_tables_ready($pdo)) {
    fwrite(STDERR, "Run migration_phase34 first.\n");
    exit(1);
}

$lines = [];
if ($file !== null && is_readable($file)) {
    $fh = fopen($file, 'r');
    if ($fh === false) {
        fwrite(STDERR, "Cannot read file.\n");
        exit(1);
    }
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) < 2) {
            continue;
        }
        $lines[] = ['name' => trim((string) $row[0]), 'qty' => (float) $row[1]];
    }
    fclose($fh);
} else {
    fwrite(STDOUT, "Paste stock_name,qty lines (empty line to finish):\n");
    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            break;
        }
        $parts = str_getcsv($line);
        if (count($parts) < 2) {
            continue;
        }
        $lines[] = ['name' => trim((string) $parts[0]), 'qty' => (float) $parts[1]];
    }
}

if ($lines === []) {
    fwrite(STDERR, "No lines.\n");
    exit(1);
}

$nameSt = $pdo->prepare(
    "SELECT id, name, on_hand, dept_id FROM stocks WHERE supplier_id IS NULL AND is_active = 1 AND name = ? LIMIT 1"
);

foreach ($lines as $line) {
    $nameSt->execute([$line['name']]);
    $stock = $nameSt->fetch(PDO::FETCH_ASSOC);
    if ($stock === false) {
        fwrite(STDERR, "Skip unknown SKU: {$line['name']}\n");
        continue;
    }
    $stockId = (int) $stock['id'];
    $qty = round((float) $line['qty'], 4);
    if ($qty <= 0) {
        continue;
    }
    fwrite(STDOUT, sprintf("Issue %s x %s to staging\n", $line['name'], $qty));
    if ($dryRun) {
        continue;
    }
    $pdo->beginTransaction();
    try {
        fulfillment_packing_issue_create($pdo, 0, [['stock_id' => $stockId, 'qty' => $qty]], 'opening balance CLI');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, $e->getMessage() . "\n");
    }
}

fwrite(STDOUT, "Done.\n");

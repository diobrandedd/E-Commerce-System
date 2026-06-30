<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();
$path = dirname(__DIR__) . '/sql/migration_phase51_bulk_honey_orders.sql';
$sql = file_get_contents($path);
if ($sql === false) {
    fwrite(STDERR, "Cannot read SQL file.\n");
    exit(1);
}

// Strip line comments, then split on semicolon at end of CREATE blocks.
$lines = preg_split('/\r\n|\r|\n/', $sql);
$buf = '';
$stmts = [];
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) {
        continue;
    }
    $buf .= $line . "\n";
    if (str_ends_with(rtrim($line), ';')) {
        $stmts[] = trim($buf);
        $buf = '';
    }
}

foreach ($stmts as $i => $stmt) {
    try {
        $pdo->exec($stmt);
        echo 'OK statement ', $i + 1, "\n";
    } catch (PDOException $e) {
        echo 'FAIL statement ', $i + 1, ': ', $e->getMessage(), "\n";
        echo substr($stmt, 0, 120), "...\n";
        exit(1);
    }
}

echo "migration_phase51_bulk_honey_orders applied\n";

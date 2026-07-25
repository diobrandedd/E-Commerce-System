<?php

declare(strict_types=1);

/**
 * Export Ian January 2026 historical partner parcels as SQL INSERTs for production.
 *
 *   php tools/export_ian_jan2026_sql.php > sql/seed_ian_january_2026_partner_orders.sql
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = ims_pdo();
$st = $pdo->query(
    "SELECT tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
            source_pipeline, partner_seller, status, ship_date, created_at
     FROM online_order_parcels
     WHERE partner_seller = 'ian'
       AND ship_date BETWEEN '2026-01-01' AND '2026-01-31'
       AND status = 'shipped'
       AND csr_batch_id IS NULL
       AND created_at LIKE '2026-01-% 12:00:00'
     ORDER BY ship_date ASC, id ASC"
);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
if ($rows === []) {
    fwrite(STDERR, "No Ian January 2026 parcels found to export.\n");
    exit(1);
}

function sql_quote(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $value) . "'";
}

$outPath = $argv[1] ?? (dirname(__DIR__) . '/sql/seed_ian_january_2026_partner_orders.sql');
$fh = fopen($outPath, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Could not write $outPath\n");
    exit(1);
}

$write = static function (string $line) use ($fh): void {
    fwrite($fh, $line);
};

$write("-- Ian partner seller - January 2026 historical orders (from FINEST HEALTH ORDERS.xlsx / JANUARY 2026)\n");
$write("-- " . count($rows) . " shipped parcels - ship_date = section Pickup Date\n");
$write("-- Run on production: mysql -u USER -p rts_system < sql/seed_ian_january_2026_partner_orders.sql\n");
$write("--\n");
$write("-- Replaces prior Ian Jan-2026 import that used ship_date=2026-01-31 for every row.\n");
$write("-- Safe if re-run: deletes that batch, then inserts (skips any other existing tracking).\n\n");
$write("SET NAMES utf8mb4;\n");
$write("SET FOREIGN_KEY_CHECKS = 0;\n\n");
$write("DELETE FROM online_order_parcels\n");
$write("WHERE partner_seller = 'ian'\n");
$write("  AND status = 'shipped'\n");
$write("  AND csr_batch_id IS NULL\n");
$write("  AND ship_date = '2026-01-31'\n");
$write("  AND created_at = '2026-01-31 12:00:00';\n\n");
$write("-- Also clear any prior pickup-dated Ian Jan rows from this seed (re-run safe).\n");
$write("DELETE FROM online_order_parcels\n");
$write("WHERE partner_seller = 'ian'\n");
$write("  AND status = 'shipped'\n");
$write("  AND csr_batch_id IS NULL\n");
$write("  AND ship_date BETWEEN '2026-01-01' AND '2026-01-31'\n");
$write("  AND created_at LIKE '2026-01-% 12:00:00';\n\n");

$chunk = 100;
$total = count($rows);
for ($i = 0; $i < $total; $i += $chunk) {
    $slice = array_slice($rows, $i, $chunk);
    $write("INSERT INTO online_order_parcels (\n");
    $write("  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,\n");
    $write("  source_pipeline, partner_seller, status, ship_date, created_at\n");
    $write(")\nSELECT * FROM (\n");
    $values = [];
    foreach ($slice as $row) {
        $parsed = $row['items_parsed_json'];
        $parsedSql = ($parsed === null || $parsed === '') ? 'NULL' : sql_quote((string) $parsed);
        $values[] = 'SELECT '
            . sql_quote((string) $row['tracking_number']) . ' AS tracking_number, '
            . sql_quote((string) $row['items_raw']) . ' AS items_raw, '
            . $parsedSql . ' AS items_parsed_json, '
            . sql_quote((string) $row['recipient_name']) . ' AS recipient_name, '
            . sql_quote((string) $row['courier']) . ' AS courier, '
            . sql_quote($row['order_no'] !== null && $row['order_no'] !== '' ? (string) $row['order_no'] : null) . ' AS order_no, '
            . sql_quote((string) $row['source_pipeline']) . ' AS source_pipeline, '
            . sql_quote((string) $row['partner_seller']) . ' AS partner_seller, '
            . sql_quote((string) $row['status']) . ' AS status, '
            . sql_quote((string) $row['ship_date']) . ' AS ship_date, '
            . sql_quote((string) $row['created_at']) . ' AS created_at';
    }
    $write(implode("\nUNION ALL\n", $values));
    $write("\n) AS t\n");
    $write("WHERE NOT EXISTS (\n");
    $write("  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number\n");
    $write(");\n\n");
}

$write("SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($fh);
fwrite(STDERR, 'Exported ' . $total . " rows → $outPath\n");

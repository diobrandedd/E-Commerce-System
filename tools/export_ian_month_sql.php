<?php

declare(strict_types=1);

/**
 * Export Ian partner historical parcels for one calendar month as SQL.
 *
 *   php tools/export_ian_month_sql.php --year=2026 --month=2 --partner=ian
 */

require_once __DIR__ . '/../includes/db.php';

function arg_value(array $argv, string $name): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $a) {
        if (str_starts_with($a, $prefix)) {
            return substr($a, strlen($prefix));
        }
    }

    return null;
}

function sql_quote(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $value) . "'";
}

$year = (int) (arg_value($argv, 'year') ?? 0);
$month = (int) (arg_value($argv, 'month') ?? 0);
$partner = strtolower(trim((string) (arg_value($argv, 'partner') ?? 'ian')));
$outArg = arg_value($argv, 'out');
$dateFromArg = arg_value($argv, 'date-from');
$dateToArg = arg_value($argv, 'date-to');

if ($year < 2020 || $month < 1 || $month > 12) {
    fwrite(STDERR, "Usage: php tools/export_ian_month_sql.php --year=2026 --month=2 [--partner=ian] [--date-from=YYYY-MM-DD] [--date-to=YYYY-MM-DD] [--out=path.sql]\n");
    exit(1);
}
if (!in_array($partner, ['ian', 'jono', 'joven'], true)) {
    fwrite(STDERR, "Invalid partner.\n");
    exit(1);
}

$first = DateTimeImmutable::createFromFormat('!Y-n-j', $year . '-' . $month . '-1');
if ($first === false) {
    fwrite(STDERR, "Invalid month.\n");
    exit(1);
}
$last = $first->modify('last day of this month');
$dateFrom = ($dateFromArg !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFromArg))
    ? $dateFromArg
    : $first->format('Y-m-d');
$dateTo = ($dateToArg !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateToArg))
    ? $dateToArg
    : $last->format('Y-m-d');
$monthLabel = $first->format('F Y');
$monthSlug = strtolower($first->format('F')) . '_' . $year;

$defaultOut = dirname(__DIR__) . '/sql/seed_' . $partner . '_' . $monthSlug . '_partner_orders.sql';
$outPath = $outArg !== null && $outArg !== '' ? $outArg : $defaultOut;

$pdo = ims_pdo();
$st = $pdo->prepare(
    "SELECT tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
            source_pipeline, partner_seller, status, ship_date, created_at
     FROM online_order_parcels
     WHERE partner_seller = ?
       AND ship_date BETWEEN ? AND ?
       AND status = 'shipped'
       AND csr_batch_id IS NULL
       AND created_at LIKE CONCAT(LEFT(ship_date, 7), '-% 12:00:00')
     ORDER BY ship_date ASC, id ASC"
);
$st->execute([$partner, $dateFrom, $dateTo]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
if ($rows === []) {
    fwrite(STDERR, "No {$partner} {$monthLabel} parcels found to export ({$dateFrom}..{$dateTo}).\n");
    exit(1);
}

$fh = fopen($outPath, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Could not write $outPath\n");
    exit(1);
}

$write = static function (string $line) use ($fh): void {
    fwrite($fh, $line);
};

$fileBase = basename($outPath);
$write("-- {$partner} partner seller - {$monthLabel} historical orders (FINEST HEALTH ORDERS.xlsx)\n");
$write('-- ' . count($rows) . " shipped parcels - ship_date = section Pickup Date\n");
$write("-- Ship date range in file: {$dateFrom} .. {$dateTo}\n");
$write("-- Run on production: mysql -u USER -p rts_system < sql/{$fileBase}\n");
$write("--\n");
$write("-- Safe re-run: deletes this batch by tracking_number, then inserts.\n\n");
$write("SET NAMES utf8mb4;\n");
$write("SET FOREIGN_KEY_CHECKS = 0;\n\n");

// Delete by tracking numbers in chunks so spillover months are covered.
$trackings = array_values(array_unique(array_map(
    static fn (array $r): string => (string) $r['tracking_number'],
    $rows
)));
foreach (array_chunk($trackings, 400) as $chunk) {
    $in = implode(', ', array_map(static fn (string $t): string => sql_quote($t), $chunk));
    $write("DELETE FROM online_order_parcels\n");
    $write("WHERE partner_seller = " . sql_quote($partner) . "\n");
    $write("  AND status = 'shipped'\n");
    $write("  AND csr_batch_id IS NULL\n");
    $write("  AND tracking_number IN ($in);\n\n");
}

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

<?php

declare(strict_types=1);

/**
 * Import partner iPYX monthly sheet into online_order_parcels (shipped, no inventory deduct).
 *
 * Example:
 *   php tools/import_historical_partner_month.php ^
 *     --file="C:\Users\Admin\Downloads\FINEST HEALTH ORDERS.xlsx" ^
 *     --sheet="JANUARY 2026" --year=2026 --month=1 --partner=ian
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/historical_orders_import.php';

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

$file = arg_value($argv, 'file');
$sheet = arg_value($argv, 'sheet') ?? '';
$year = (int) (arg_value($argv, 'year') ?? 0);
$month = (int) (arg_value($argv, 'month') ?? 0);
$partner = arg_value($argv, 'partner') ?? 'ian';
$pipeline = arg_value($argv, 'pipeline') ?? 'partner';

if ($file === null || $file === '' || $year < 1 || $month < 1) {
    fwrite(STDERR, "Usage: php tools/import_historical_partner_month.php --file=PATH --sheet=\"JANUARY 2026\" --year=2026 --month=1 --partner=ian\n");
    exit(1);
}

try {
    $result = historical_orders_import_file(ims_pdo(), $file, [
        'year' => $year,
        'month' => $month,
        'pipeline' => $pipeline,
        'partner_seller' => $partner,
        'sheet' => $sheet,
    ]);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

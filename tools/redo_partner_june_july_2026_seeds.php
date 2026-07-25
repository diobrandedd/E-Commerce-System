<?php

declare(strict_types=1);

/**
 * Redo June + July 2026 partner seeds for ian / joven / jono.
 * Uses Excel column H Tracking Number as tracking_number when present;
 * Order No. is stored in order_no (and used as TN fallback when waybill is blank).
 *
 *   php tools/redo_partner_june_july_2026_seeds.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/historical_orders_import.php';

$php = 'c:\\xampp\\php\\php.exe';
$year = 2026;

/** @var list<array{partner:string,file:string,months:list<array{month:int,sheet:string}>}> */
$jobs = [
    [
        'partner' => 'ian',
        'file' => 'C:\\Users\\Admin\\Downloads\\FINEST HEALTH ORDERS.xlsx',
        'months' => [
            ['month' => 6, 'sheet' => 'JUNE 2026'],
            ['month' => 7, 'sheet' => 'JULY 2026'],
        ],
    ],
    [
        'partner' => 'joven',
        'file' => 'C:\\Users\\Admin\\Downloads\\SIR JOVEN FINEST WELLNESS PH ORDERS.xlsx',
        'months' => [
            ['month' => 6, 'sheet' => 'JUNE 2026'],
            ['month' => 7, 'sheet' => 'JULY 2026'],
        ],
    ],
    [
        'partner' => 'jono',
        'file' => 'C:\\Users\\Admin\\Downloads\\SIR JONO (FWELLNESS) ORDERS.xlsx',
        'months' => [
            ['month' => 6, 'sheet' => 'JUNE 2026'],
            ['month' => 7, 'sheet' => 'JULY 2026'],
        ],
    ],
];

$pdo = ims_pdo();
$summary = [];

foreach ($jobs as $job) {
    $partner = (string) $job['partner'];
    $file = (string) $job['file'];
    if (!is_file($file)) {
        fwrite(STDERR, "Workbook not found for {$partner}: {$file}\n");
        exit(1);
    }

    foreach ($job['months'] as $cfg) {
        $month = (int) $cfg['month'];
        $sheet = (string) $cfg['sheet'];
        $first = DateTimeImmutable::createFromFormat('!Y-n-j', $year . '-' . $month . '-1');
        if ($first === false) {
            throw new RuntimeException('Bad month');
        }
        $label = $first->format('F Y');
        $monthSlug = strtolower($first->format('F')) . '_' . $year;
        $sqlName = 'seed_' . $partner . '_' . $monthSlug . '_partner_orders.sql';
        $monthStart = $first->format('Y-m-d');
        $monthEnd = $first->modify('last day of this month')->format('Y-m-d');

        fwrite(STDERR, "=== {$partner} / {$label} / {$sheet} ===\n");

        // Remove prior historical partner seeds for this month (waybill-keyed or order-no-keyed).
        $clear = $pdo->prepare(
            "DELETE FROM online_order_parcels
             WHERE partner_seller = ?
               AND status = 'shipped'
               AND csr_batch_id IS NULL
               AND ship_date BETWEEN ? AND ?
               AND created_at LIKE CONCAT(LEFT(ship_date, 7), '-% 12:00:00')"
        );
        $clear->execute([$partner, $monthStart, $monthEnd]);
        $cleared = $clear->rowCount();

        $parsed = historical_orders_run_python_parse($file, $sheet);
        $orders = is_array($parsed['orders'] ?? null) ? $parsed['orders'] : [];
        $inMonthTns = [];
        $spillTns = [];
        $usedTn = [];
        $withExcelTn = 0;
        foreach ($orders as $order) {
            if (!is_array($order)) {
                continue;
            }
            $orderNo = trim((string) ($order['order_no'] ?? ''));
            $tnRaw = trim((string) ($order['tracking_number'] ?? ''));
            if ($tnRaw !== '') {
                $withExcelTn++;
            }
            // Prefer Excel Tracking Number (col H) when present; else Order No.
            $tn = historical_orders_normalize_tn($tnRaw);
            if ($tn === '' && $orderNo !== '') {
                $tn = historical_orders_normalize_tn($orderNo);
            }
            if ($tn === '') {
                continue;
            }
            $baseTn = $tn;
            $suffix = 2;
            while (isset($usedTn[$tn])) {
                $tn = $baseTn . 'D' . $suffix;
                $suffix++;
            }
            $usedTn[$tn] = true;

            $sd = trim((string) ($order['ship_date'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sd)) {
                continue;
            }
            if ($sd >= $monthStart && $sd <= $monthEnd) {
                $inMonthTns[$tn] = true;
            } else {
                $spillTns[$tn] = true;
            }
        }
        $tnList = array_keys($inMonthTns);
        sort($tnList);
        $spillList = array_keys($spillTns);

        fwrite(STDERR, "cleared_old={$cleared} parsed_in_month=" . count($tnList)
            . ' spill_skip=' . count($spillList)
            . " excel_tn={$withExcelTn}\n");

        $prev = set_error_handler(static function (int $severity, string $errstr): bool {
            return str_contains($errstr, 'preg_match(): Unknown modifier');
        });
        try {
            $result = historical_orders_import_file($pdo, $file, [
                'year' => $year,
                'month' => $month,
                'pipeline' => 'partner',
                'partner_seller' => $partner,
                'sheet' => $sheet,
            ]);
        } finally {
            if ($prev !== null) {
                set_error_handler($prev);
            } else {
                restore_error_handler();
            }
        }

        $spillDeleted = 0;
        foreach (array_chunk($spillList, 400) as $chunk) {
            if ($chunk === []) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $st = $pdo->prepare(
                "DELETE FROM online_order_parcels
                 WHERE partner_seller = ?
                   AND status = 'shipped'
                   AND csr_batch_id IS NULL
                   AND tracking_number IN ($placeholders)"
            );
            $st->execute(array_merge([$partner], $chunk));
            $spillDeleted += $st->rowCount();
        }

        $inserted = (int) ($result['commit']['inserted'] ?? 0);
        $conflicts = (int) ($result['preview_counts']['conflicts'] ?? 0);
        $failed = (int) ($result['preview_counts']['failed'] ?? 0);
        fwrite(STDERR, "inserted={$inserted} conflicts={$conflicts} failed={$failed} spill_deleted={$spillDeleted}\n");

        $outPath = dirname(__DIR__) . '/sql/' . $sqlName;
        $cmd = $php . ' ' . escapeshellarg(__DIR__ . '/export_ian_month_sql.php')
            . ' --year=' . $year
            . ' --month=' . $month
            . ' --partner=' . escapeshellarg($partner)
            . ' --date-from=' . escapeshellarg($monthStart)
            . ' --date-to=' . escapeshellarg($monthEnd)
            . ' --out=' . escapeshellarg($outPath)
            . ' 2>&1';
        $out = shell_exec($cmd);
        fwrite(STDERR, trim((string) $out) . "\n");

        // Spot-check: no JT / long Flash waybill patterns as tracking when order_no differs.
        $sample = $pdo->prepare(
            "SELECT tracking_number, order_no FROM online_order_parcels
             WHERE partner_seller = ? AND ship_date BETWEEN ? AND ?
               AND created_at LIKE CONCAT(LEFT(ship_date, 7), '-% 12:00:00')
             ORDER BY id ASC LIMIT 3"
        );
        $sample->execute([$partner, $monthStart, $monthEnd]);
        $samples = $sample->fetchAll(PDO::FETCH_ASSOC);

        $countSt = $pdo->prepare(
            "SELECT COUNT(*) FROM online_order_parcels
             WHERE partner_seller = ? AND ship_date BETWEEN ? AND ?
               AND created_at LIKE CONCAT(LEFT(ship_date, 7), '-% 12:00:00')"
        );
        $countSt->execute([$partner, $monthStart, $monthEnd]);
        $dbCount = (int) $countSt->fetchColumn();

        $summary[] = [
            'partner' => $partner,
            'month' => $label,
            'inserted' => $inserted,
            'conflicts' => $conflicts,
            'failed' => $failed,
            'db_count' => $dbCount,
            'samples' => $samples,
            'sql' => 'sql/' . $sqlName,
        ];
    }
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

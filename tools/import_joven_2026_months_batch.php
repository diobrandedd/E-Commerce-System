<?php

declare(strict_types=1);

/**
 * Batch-import Joven 2026 months (Mar–Jul) and write per-month SQL seeds.
 *
 *   php tools/import_joven_2026_months_batch.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/historical_orders_import.php';

$file = 'C:\\Users\\Admin\\Downloads\\SIR JOVEN FINEST WELLNESS PH ORDERS.xlsx';
$partner = 'joven';
$year = 2026;
$php = 'c:\\xampp\\php\\php.exe';

/** @var list<array{month:int,sheet:string}> */
$months = [
    ['month' => 3, 'sheet' => 'MARCH 2026'],
    ['month' => 4, 'sheet' => 'APRIL 2026'],
    ['month' => 5, 'sheet' => 'MAY 2026'],
    ['month' => 6, 'sheet' => 'JUNE 2026'],
    ['month' => 7, 'sheet' => 'JULY 2026'],
];

if (!is_file($file)) {
    fwrite(STDERR, "Workbook not found: $file\n");
    exit(1);
}

$pdo = ims_pdo();
$summary = [];

foreach ($months as $cfg) {
    $month = (int) $cfg['month'];
    $sheet = (string) $cfg['sheet'];
    $first = DateTimeImmutable::createFromFormat('!Y-n-j', $year . '-' . $month . '-1');
    if ($first === false) {
        throw new RuntimeException('Bad month');
    }
    $label = $first->format('F Y');
    $monthSlug = strtolower($first->format('F')) . '_' . $year;
    $sqlName = 'seed_' . $partner . '_' . $monthSlug . '_partner_orders.sql';

    fwrite(STDERR, "=== {$label} / {$sheet} ===\n");

    $monthStart = $first->format('Y-m-d');
    $monthEnd = $first->modify('last day of this month')->format('Y-m-d');

    $parsed = historical_orders_run_python_parse($file, $sheet);
    $orders = is_array($parsed['orders'] ?? null) ? $parsed['orders'] : [];
    $inMonthTns = [];
    $spillTns = [];
    $usedTn = [];
    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        $orderNo = trim((string) ($order['order_no'] ?? ''));
        $tnRaw = trim((string) ($order['tracking_number'] ?? ''));
        // Prefer Excel Tracking Number (col H) when present; else Order No.
        $tn = historical_orders_normalize_tn($tnRaw);
        if ($tn === '' && $orderNo !== '') {
            $tn = historical_orders_normalize_tn($orderNo);
        }
        if ($tn === '') {
            continue;
        }
        // Match import uniqueness: duplicate order nos get D2, D3, ...
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
            // March sheet lists early-April pickups that also appear on APRIL sheet.
            $spillTns[$tn] = true;
        }
    }
    $tnList = array_keys($inMonthTns);
    sort($tnList);
    $spillList = array_keys($spillTns);
    sort($spillList);

    $deleted = 0;
    foreach (array_chunk($tnList, 400) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $st = $pdo->prepare(
            "DELETE FROM online_order_parcels
             WHERE partner_seller = ?
               AND status = 'shipped'
               AND csr_batch_id IS NULL
               AND tracking_number IN ($placeholders)"
        );
        $st->execute(array_merge([$partner], $chunk));
        $deleted += $st->rowCount();
    }
    fwrite(STDERR, 'parsed_in_month=' . count($tnList)
        . ' spill_skip=' . count($spillList)
        . " deleted_old={$deleted} ship_range={$monthStart}..{$monthEnd}\n");

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

    // Drop calendar-month spillover rows imported from this sheet (owned by later month).
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

    $count = 0;
    foreach (array_chunk($tnList, 400) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM online_order_parcels
             WHERE partner_seller = ? AND tracking_number IN ($placeholders)"
        );
        $st->execute(array_merge([$partner], $chunk));
        $count += (int) $st->fetchColumn();
    }

    $dst = $pdo->prepare(
        "SELECT ship_date, COUNT(*) c FROM online_order_parcels
         WHERE partner_seller = ? AND ship_date BETWEEN ? AND ?
           AND created_at LIKE CONCAT(LEFT(ship_date, 7), '-% 12:00:00')
         GROUP BY ship_date ORDER BY ship_date"
    );
    $dst->execute([$partner, $monthStart, $monthEnd]);
    $byDate = [];
    while ($row = $dst->fetch(PDO::FETCH_ASSOC)) {
        $byDate[(string) $row['ship_date']] = (int) $row['c'];
    }

    $summary[] = [
        'month' => $label,
        'sheet' => $sheet,
        'inserted' => $inserted,
        'conflicts' => $conflicts,
        'failed' => $failed,
        'db_count' => $count,
        'ship_range' => [$monthStart, $monthEnd],
        'ship_dates' => $byDate,
        'sql' => 'sql/' . $sqlName,
    ];
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

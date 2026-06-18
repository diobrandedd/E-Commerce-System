<?php

declare(strict_types=1);

/**
 * Parcel pack tier regression — used by tools/verify.ps1.
 * Run: php tools/test_parcel_pack_match.php
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/honey_parcel_item_parser.php';
require_once dirname(__DIR__) . '/includes/parcel_pack_costing.php';

$pdo = rts_pdo();

/** @var array<string, string> */
$tests = [
    '1L HONEY Light x1' => 'honey_1x_1L',
    'Paragis Tea x2' => 'dept_pt_1_2',
    '3x 350ml (L)' => 'honey_3x_350ml',
    '350ml (L) x1' => 'honey_350ml_solo',
    '36x 350ml (L)' => 'bulk_36x_350ml',
    'Jar Cacao x1; 350ml (L) x1' => 'combo_cacao_350ml_honey',
];

$failed = 0;
foreach ($tests as $raw => $expect) {
    $parsed = honey_parcel_parse_items_string($raw);
    $shape = parcel_pack_order_shape_from_parsed($parsed);
    $p = parcel_pack_preview_from_items_raw($pdo, $raw);
    $got = (string) ($p['tier_key'] ?? 'NONE');
    $ok = $got === $expect;
    echo $raw . ' => ' . $got . ($ok ? ' OK' : ' EXPECT ' . $expect);
    echo ' shape=' . json_encode($shape, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

// Phase 37: 350ml solo uses half LAD piece (0.5 pcs)
try {
    $soloLines = parcel_pack_fulfillment_lines_for_items_raw($pdo, '350ml (L) x1');
    $ladQty = null;
    foreach ($soloLines as $line) {
        if (str_contains((string) ($line['stock_name'] ?? ''), 'LAD')) {
            $ladQty = (float) ($line['qty'] ?? 0);
            break;
        }
    }
    if ($ladQty === null || abs($ladQty - 0.5) > 0.0001) {
        echo '350ml solo LAD qty => ' . var_export($ladQty, true) . ' EXPECT 0.5' . PHP_EOL;
        $failed++;
    } else {
        echo '350ml solo LAD qty => 0.5 OK' . PHP_EOL;
    }
} catch (Throwable $e) {
    echo '350ml solo LAD qty => FAIL ' . $e->getMessage() . PHP_EOL;
    $failed++;
}

// Phase 37: bulk 36x350 uses column units on cushion rolls
try {
    $bulkLines = parcel_pack_fulfillment_lines_for_items_raw($pdo, '36x 350ml (L)');
    $hasColumn = false;
    foreach ($bulkLines as $line) {
        if (($line['unit'] ?? '') === 'column') {
            $hasColumn = true;
            break;
        }
    }
    if (!$hasColumn) {
        echo 'bulk_36x_350ml column lines => missing EXPECT column unit on cushion roll' . PHP_EOL;
        $failed++;
    } else {
        echo 'bulk_36x_350ml column lines => OK' . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'bulk_36x_350ml column lines => FAIL ' . $e->getMessage() . PHP_EOL;
    $failed++;
}

// Phase 37: cacao combo uses foam_piece
try {
    $cacaoLines = parcel_pack_fulfillment_lines_for_items_raw($pdo, 'Jar Cacao x1; 350ml (L) x1');
    $foam = false;
    foreach ($cacaoLines as $line) {
        if (($line['unit'] ?? '') === 'foam_piece') {
            $foam = true;
            break;
        }
    }
    if (!$foam) {
        echo 'combo_cacao_350ml foam_piece => missing EXPECT foam_piece line' . PHP_EOL;
        $failed++;
    } else {
        echo 'combo_cacao_350ml foam_piece => OK' . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'combo_cacao_350ml foam_piece => FAIL ' . $e->getMessage() . PHP_EOL;
    $failed++;
}

exit($failed > 0 ? 1 : 0);

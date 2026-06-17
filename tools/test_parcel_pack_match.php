<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/honey_parcel_item_parser.php';
require_once dirname(__DIR__) . '/includes/parcel_pack_costing.php';

$pdo = rts_pdo();
$tests = [
    '1L HONEY Light x1' => 'honey_1x_1L',
    'Paragis Tea x2' => 'dept_pt_1_2',
    '350ml Honey Light x3' => 'honey_3x_350ml',
];
foreach ($tests as $raw => $expect) {
    $parsed = honey_parcel_parse_items_string($raw);
    $shape = parcel_pack_order_shape_from_parsed($parsed);
    $p = parcel_pack_preview_from_items_raw($pdo, $raw);
    $got = $p['tier_key'] ?? 'NONE';
    echo $raw . ' => ' . $got . ($got === $expect ? ' OK' : ' EXPECT ' . $expect);
    echo ' shape=' . json_encode($shape) . PHP_EOL;
}

<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/honey_parcel_item_parser.php';
require_once dirname(__DIR__) . '/includes/parcel_pack_costing.php';

$pdo = rts_pdo();
foreach (['350ml (L) x1', '36x 350ml (L)', 'Jar Cacao x1; 350ml (L) x1'] as $raw) {
    $p = parcel_pack_preview_from_items_raw($pdo, $raw);
    echo $raw . ' tier=' . ($p['tier_key'] ?? 'NULL') . PHP_EOL;
    if (!empty($p['tiers'][0]['lines'])) {
        foreach ($p['tiers'][0]['lines'] as $l) {
            echo '  line stock_id=' . var_export($l['stock_id'] ?? null, true) . ' unit=' . ($l['unit'] ?? '') . PHP_EOL;
        }
    } else {
        echo '  no lines' . PHP_EOL;
    }
}

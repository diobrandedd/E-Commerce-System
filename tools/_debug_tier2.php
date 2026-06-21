<?php
require_once dirname(__DIR__) . '/includes/db.php';
$pdo = rts_pdo();
$rows = $pdo->query('SELECT tier_key, sort_order, match_rule, is_active FROM parcel_pack_tiers ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
echo 'Tier count: ' . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo $r['sort_order'] . ' ' . $r['tier_key'] . ' active=' . $r['is_active'] . ' rule=' . $r['match_rule'] . PHP_EOL;
}
$lineCount = (int) $pdo->query('SELECT COUNT(*) FROM parcel_pack_tier_lines')->fetchColumn();
echo 'Line count: ' . $lineCount . PHP_EOL;

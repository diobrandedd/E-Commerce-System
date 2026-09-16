<?php

declare(strict_types=1);

/**
 * Consolidate duplicate honey production lots for the same
 * supplier_code + arrival_date + variant (across stock-out days).
 *
 * Usage:
 *   php tools/consolidate_honey_production_daily_lots.php           # dry-run
 *   php tools/consolidate_honey_production_daily_lots.php --apply   # write
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/honey_inventory.php';

$apply = in_array('--apply', $argv, true);
$pdo = ims_pdo();

$rows = $pdo->query(
    'SELECT id, warehouse_row_id, supplier_code, arrival_date, variant,
            COALESCE(stock_out_date, DATE(transferred_at)) AS stock_out_day,
            daily_group_key, current_qty, initial_qty
     FROM honey_production_rows
     ORDER BY supplier_code ASC, arrival_date ASC, variant ASC, id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

/** @var array<string, list<array<string,mixed>>> $groups */
$groups = [];
foreach ($rows as $row) {
    $key = strtoupper((string) $row['supplier_code'])
        . '|' . (string) $row['arrival_date']
        . '|' . (string) $row['variant'];
    $groups[$key][] = $row;
}

$plans = [];
$skipped = [];

foreach ($groups as $groupKey => $members) {
    if (count($members) < 2) {
        continue;
    }

    // Prefer overdrawn keeper so restocks land on the shortfall lot; else lowest id.
    usort($members, static function (array $a, array $b): int {
        $aOver = ((float) $a['current_qty']) < 0 ? 0 : 1;
        $bOver = ((float) $b['current_qty']) < 0 ? 0 : 1;
        if ($aOver !== $bOver) {
            return $aOver <=> $bOver;
        }

        return ((int) $a['id']) <=> ((int) $b['id']);
    });

    $keeper = $members[0];
    $dupes = array_slice($members, 1);
    $conflict = null;

    foreach ($dupes as $dupe) {
        $pkConflict = $pdo->prepare(
            'SELECT pk.id
             FROM honey_packaging_rows pk
             INNER JOIN honey_packaging_rows keep
               ON keep.production_row_id = ?
              AND keep.batch_no = pk.batch_no
              AND keep.variant = pk.variant
              AND keep.bottling_date = pk.bottling_date
             WHERE pk.production_row_id = ?
             LIMIT 1'
        );
        $pkConflict->execute([(int) $keeper['id'], (int) $dupe['id']]);
        if ($pkConflict->fetchColumn() !== false) {
            $conflict = 'packaging uniqueness conflict with keeper #' . (int) $keeper['id']
                . ' vs dupe #' . (int) $dupe['id'];
            break;
        }
    }

    if ($conflict !== null) {
        $skipped[] = ['group' => $groupKey, 'reason' => $conflict, 'ids' => array_map(
            static fn (array $m): int => (int) $m['id'],
            $members
        )];
        continue;
    }

    $sumCurrent = 0.0;
    $sumInitial = 0.0;
    $latestStockOut = (string) $keeper['stock_out_day'];
    foreach ($members as $m) {
        $sumCurrent += (float) $m['current_qty'];
        $sumInitial += (float) $m['initial_qty'];
        $day = (string) $m['stock_out_day'];
        if ($day !== '' && ($latestStockOut === '' || $day > $latestStockOut)) {
            $latestStockOut = $day;
        }
    }

    $supplierId = 0;
    $whId = (int) $keeper['warehouse_row_id'];
    if ($whId > 0) {
        $wh = honey_fetch_warehouse($pdo, $whId);
        if ($wh) {
            $supplierId = (int) ($wh['supplier_id'] ?? 0);
        }
    }
    $dailyKey = honey_production_daily_group_key(
        $supplierId,
        strtoupper((string) $keeper['supplier_code']),
        (string) $keeper['arrival_date'],
        (string) $keeper['variant']
    );

    $plans[] = [
        'group' => $groupKey,
        'keeper_id' => (int) $keeper['id'],
        'merge_ids' => array_map(static fn (array $m): int => (int) $m['id'], $dupes),
        'current_qty' => round($sumCurrent, 2),
        'initial_qty' => round($sumInitial, 2),
        'daily_group_key' => $dailyKey,
        'stock_out_date' => $latestStockOut,
    ];
}

echo ($apply ? "APPLY" : "DRY-RUN") . " honey production daily lot consolidate\n";
echo 'Groups to merge: ' . count($plans) . "\n";
echo 'Groups skipped: ' . count($skipped) . "\n";

foreach ($plans as $plan) {
    echo sprintf(
        "- keeper #%d <- merge [%s] → current=%s initial=%s key=%s\n",
        $plan['keeper_id'],
        implode(',', $plan['merge_ids']),
        $plan['current_qty'],
        $plan['initial_qty'],
        $plan['daily_group_key']
    );
}
foreach ($skipped as $s) {
    echo sprintf(
        "! skip %s (%s) ids=[%s]\n",
        $s['group'],
        $s['reason'],
        implode(',', $s['ids'])
    );
}

if (!$apply) {
    echo "Re-run with --apply to write changes.\n";
    exit(0);
}

if ($plans === []) {
    echo "Nothing to apply.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    foreach ($plans as $plan) {
        $keeperId = (int) $plan['keeper_id'];
        foreach ($plan['merge_ids'] as $dupeId) {
            $dupeId = (int) $dupeId;
            $pdo->prepare(
                'UPDATE honey_packaging_rows SET production_row_id = ? WHERE production_row_id = ?'
            )->execute([$keeperId, $dupeId]);
            $pdo->prepare(
                'UPDATE honey_warehouse_stock_out_events SET production_row_id = ? WHERE production_row_id = ?'
            )->execute([$keeperId, $dupeId]);
            $pdo->prepare('DELETE FROM honey_production_rows WHERE id = ?')->execute([$dupeId]);
        }
        $pdo->prepare(
            'UPDATE honey_production_rows
             SET current_qty = ?, initial_qty = ?, stock_out_date = ?, daily_group_key = ?
             WHERE id = ?'
        )->execute([
            $plan['current_qty'],
            $plan['initial_qty'],
            $plan['stock_out_date'],
            $plan['daily_group_key'],
            $keeperId,
        ]);
    }
    $pdo->commit();
    echo 'Applied ' . count($plans) . " merge group(s).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAILED: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

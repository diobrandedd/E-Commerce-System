<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/suppliers.php';
require_once __DIR__ . '/honey_volume.php';
require_once __DIR__ . '/catalog_bootstrap.php';
require_once __DIR__ . '/material_inventory.php';
require_once __DIR__ . '/dept_production.php';
require_once __DIR__ . '/inventory_edit_log.php';

/** Lazy-load pricing helpers (avoids circular require at bootstrap). */
function honey_inventory_pricing(): void
{
    static $loaded = false;
    if (!$loaded) {
        require_once __DIR__ . '/honey_pricing.php';
        $loaded = true;
    }
}

const HONEY_VARIANTS = ['light', 'amber', 'dark', 'other'];
const HONEY_SIZES = ['liter', '250ml', '350ml', 'gallon'];

/** Empty/overdrawn production lots auto-deactivate after this many hours. */
const HONEY_PRODUCTION_AUTO_DEACTIVATE_HOURS = 1;

/**
 * Whether honey_production_rows.depleted_at exists (phase 88).
 */
function honey_production_has_depleted_at(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM honey_production_rows LIKE 'depleted_at'");
        $ready = $st !== false && $st->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable $e) {
        $ready = false;
    }

    return $ready;
}

/**
 * Deactivate lots that have been empty/overdrawn for >= HONEY_PRODUCTION_AUTO_DEACTIVATE_HOURS.
 * Lazy sweep — no cron required. Safe no-op before phase 88.
 *
 * @return int rows deactivated
 */
function honey_production_auto_deactivate_stale_empty(PDO $pdo): int
{
    if (!honey_production_has_depleted_at($pdo)) {
        return 0;
    }
    $hours = max(1, (int) HONEY_PRODUCTION_AUTO_DEACTIVATE_HOURS);
    $st = $pdo->prepare(
        "UPDATE honey_production_rows
         SET is_active = 0
         WHERE COALESCE(is_active, 1) = 1
           AND current_qty <= 0
           AND depleted_at IS NOT NULL
           AND depleted_at <= (NOW() - INTERVAL {$hours} HOUR)"
    );
    $st->execute();

    return $st->rowCount();
}

/**
 * Lots that still hold gallons must stay active — inactive+stock made Available show
 * gallons while FIFO (older builds) threw "No production gallons".
 *
 * @return int rows reactivated
 */
function honey_production_auto_reactivate_with_stock(PDO $pdo): int
{
    $sql = honey_production_has_depleted_at($pdo)
        ? 'UPDATE honey_production_rows
            SET is_active = 1, depleted_at = NULL
            WHERE COALESCE(is_active, 1) = 0
              AND current_qty > 0'
        : 'UPDATE honey_production_rows
            SET is_active = 1
            WHERE COALESCE(is_active, 1) = 0
              AND current_qty > 0';
    $st = $pdo->prepare($sql);
    $st->execute();

    return $st->rowCount();
}

/**
 * Keep active flags consistent: reactivate stocked lots, then retire long-empty ones.
 */
function honey_production_sync_active_flags(PDO $pdo): void
{
    honey_production_auto_reactivate_with_stock($pdo);
    honey_production_auto_deactivate_stale_empty($pdo);
}

function honey_role_can_view(array $user): bool
{
    return ims_user_can($user, 'honey.view');
}

function honey_role_can_mutate(array $user): bool
{
    return ims_user_can($user, 'honey.mutate');
}

function honey_role_can_honey_ops(array $user): bool
{
    return ims_user_can($user, 'honey.ops');
}

function honey_role_can_dept_produce(array $user, ?string $productKey = null): bool
{
    if ($productKey !== null && trim($productKey) !== '') {
        return ims_user_can_dept_line($user, $productKey, 'produce');
    }

    return ims_user_can($user, 'honey.dept_produce')
        || ims_user_can($user, 'dept.paragis.produce')
        || ims_user_can($user, 'dept.brc.produce')
        || ims_user_can($user, 'dept.cacao.produce')
        || ims_user_can($user, 'dept.comb.produce');
}

function honey_role_can_executive_edit(array $user): bool
{
    return ims_user_can($user, 'honey.executive_edit');
}

/**
 * Map catalog product_key → department name key used for staff/TL scope.
 */
function honey_dept_key_for_product_key(string $productKey): ?string
{
    $productKey = strtolower(trim($productKey));

    return match ($productKey) {
        'pt' => 'paragis',
        'brc', 'jar_cacao' => 'cacao_brc',
        'honey_comb' => 'honey',
        default => null,
    };
}

function honey_supplier_code(string $supplierName, string $arrivalDate): string
{
    $words = preg_split('/\s+/', trim($supplierName)) ?: [];
    $initials = '';
    foreach ($words as $w) {
        if ($w !== '') {
            $initials .= strtoupper($w[0]);
        }
    }
    if ($initials === '') {
        $initials = 'XX';
    }
    $ts = strtotime($arrivalDate);
    $mmddyy = $ts ? date('mdy', $ts) : date('mdy');

    return $initials . $mmddyy;
}

function honey_batch_no(string $supplierCode, string $bottlingDate): string
{
    $ts = strtotime($bottlingDate);
    $mmddyy = $ts ? date('mdy', $ts) : date('mdy');

    return $supplierCode . '-' . $mmddyy;
}

function honey_normalize_variant(string $v): string
{
    $v = strtolower(trim($v));
    if (!in_array($v, HONEY_VARIANTS, true)) {
        throw new RuntimeException('Invalid variant.');
    }

    return $v;
}

/**
 * Normalize bottling date to Y-m-d (server local calendar). Empty → today.
 */
function honey_normalize_bottling_date(?string $date = null): string
{
    $date = $date !== null ? trim($date) : '';
    if ($date === '') {
        return date('Y-m-d');
    }
    $ts = strtotime($date);
    if ($ts === false) {
        throw new RuntimeException('Invalid bottling date.');
    }

    return date('Y-m-d', $ts);
}

/**
 * Allocate a packaging batch_no unique for (variant, bottling_date).
 * The same human label may exist on other variants or other bottling dates.
 * Appends -2, -3… only when the same base already exists for that variant+date.
 */
function honey_unique_packaging_batch_no(
    PDO $pdo,
    string $base,
    string $variant,
    ?string $bottlingDate = null
): string {
    $variant = honey_normalize_variant($variant);
    $bottlingDate = honey_normalize_bottling_date($bottlingDate);
    $candidate = $base;
    $n = 2;
    $st = $pdo->prepare(
        'SELECT 1 FROM honey_packaging_rows
         WHERE batch_no = ? AND variant = ? AND bottling_date = ? LIMIT 1'
    );
    while (true) {
        $st->execute([$candidate, $variant, $bottlingDate]);
        if ($st->fetchColumn() === false) {
            return $candidate;
        }
        if ($n > 99) {
            throw new RuntimeException('Could not allocate a unique batch number.');
        }
        $candidate = $base . '-' . $n;
        $n++;
    }
}

/** @return array<string, mixed>|null */
function honey_fetch_packaging_by_batch_variant_date(
    PDO $pdo,
    string $batchNo,
    string $variant,
    ?string $bottlingDate = null
): ?array {
    $variant = honey_normalize_variant($variant);
    $bottlingDate = honey_normalize_bottling_date($bottlingDate);
    $st = $pdo->prepare(
        'SELECT * FROM honey_packaging_rows
         WHERE batch_no = ? AND variant = ? AND bottling_date = ? LIMIT 1'
    );
    $st->execute([trim($batchNo), $variant, $bottlingDate]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Find or create a packaging lot for stock-in on a bottling date (default today).
 * Does not rewrite an existing row's bottling_date.
 *
 * @return array<string, mixed>
 */
function honey_ensure_packaging_row_for_stock_in(
    PDO $pdo,
    int $userId,
    string $batchNo,
    string $variant,
    string $supplierCode,
    ?string $bottlingDate = null,
    ?int $productionRowId = null
): array {
    $variant = honey_normalize_variant($variant);
    $bottlingDate = honey_normalize_bottling_date($bottlingDate);
    $batchNo = trim($batchNo);
    if ($batchNo === '') {
        throw new RuntimeException('Batch number is required.');
    }
    $existing = honey_fetch_packaging_by_batch_variant_date($pdo, $batchNo, $variant, $bottlingDate);
    if ($existing !== null) {
        return $existing;
    }

    $prodId = ($productionRowId !== null && $productionRowId > 0) ? $productionRowId : null;
    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
    );
    $ins->execute([
        $prodId,
        $supplierCode,
        $batchNo,
        $bottlingDate,
        $variant,
        $userId,
    ]);
    $pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());
    if ($pack === null) {
        throw new RuntimeException('Could not create packaging lot.');
    }

    return $pack;
}

const HONEY_PACKAGING_POOL_BATCH = 'POOL';
const HONEY_PACKAGING_POOL_DATE = '2000-01-01';

function honey_packaging_is_pool_row(array $row): bool
{
    return (string) ($row['batch_no'] ?? '') === HONEY_PACKAGING_POOL_BATCH
        && (string) ($row['bottling_date'] ?? '') === HONEY_PACKAGING_POOL_DATE;
}

/**
 * One finished-goods pool row per Light / Amber / Dark (no lot / date).
 *
 * @return array<string, mixed>
 */
function honey_packaging_ensure_variant_pool(PDO $pdo, int $userId, string $variant): array
{
    $variant = honey_normalize_variant($variant);
    if (!in_array($variant, ['light', 'amber', 'dark'], true)) {
        throw new RuntimeException('Variant pools are only for Light, Amber, and Dark.');
    }
    $existing = honey_fetch_packaging_by_batch_variant_date(
        $pdo,
        HONEY_PACKAGING_POOL_BATCH,
        $variant,
        HONEY_PACKAGING_POOL_DATE
    );
    if ($existing !== null) {
        return $existing;
    }

    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (NULL, "", ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
    );
    $ins->execute([
        HONEY_PACKAGING_POOL_BATCH,
        HONEY_PACKAGING_POOL_DATE,
        $variant,
        $userId > 0 ? $userId : 1,
    ]);
    $pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());
    if ($pack === null) {
        throw new RuntimeException('Could not create packaging pool.');
    }

    return $pack;
}

/**
 * FIFO deduct gallons from production lots of one variant.
 * Drains every lot with positive gallons (oldest stock-out first), including inactive
 * lots that still hold stock, before any overdraw. Overdraw only after the variant
 * has no positive gallons left — never deepen one row while another still has stock.
 *
 * @return array{used_gallons: float, remaining_production_gallons: float, production_overdrawn: bool, warning: ?string, lot_deductions: list<array<string,mixed>>, ml_per_gallon: float}
 */
function honey_production_fifo_deduct_gallons(PDO $pdo, string $variant, float $usedGallons): array
{
    $variant = honey_normalize_variant($variant);
    if ($usedGallons <= 0) {
        throw new RuntimeException('Bottle volume must be greater than zero.');
    }

    honey_production_sync_active_flags($pdo);

    // Lock all lots for the variant. Empty inactive shells are kept only as possible
    // overdraw anchors when nothing else exists; positive inactive stock is drained.
    $st = $pdo->prepare(
        'SELECT id, supplier_code, current_qty, initial_qty,
                COALESCE(is_active, 1) AS is_active,
                COALESCE(stock_out_date, DATE(transferred_at)) AS stock_out_date
         FROM honey_production_rows
         WHERE variant = ?
         ORDER BY COALESCE(stock_out_date, DATE(transferred_at)) ASC, id ASC
         FOR UPDATE'
    );
    $st->execute([$variant]);
    $lots = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($lots === []) {
        throw new RuntimeException(
            'No production gallons for ' . ucfirst($variant) . '. Stock out from warehouse first.'
        );
    }

    $remainingNeed = $usedGallons;
    $deductions = [];
    $totalRemainingAfter = 0.0;
    foreach ($lots as $lot) {
        $totalRemainingAfter += (float) $lot['current_qty'];
    }

    $lastTouchedId = 0;
    $lastTouchedCode = '';

    $takeFromLot = static function (int $lotId, string $code, float $take, bool $overdraw = false) use (
        $pdo,
        &$remainingNeed,
        &$totalRemainingAfter,
        &$deductions,
        &$lastTouchedId,
        &$lastTouchedCode
    ): void {
        if ($take <= 1e-9) {
            return;
        }
        honey_production_deduct_gallons($pdo, $lotId, $take);
        $remainingNeed -= $take;
        $totalRemainingAfter -= $take;
        $row = [
            'production_row_id' => $lotId,
            'supplier_code' => $code,
            'taken_gallons' => round($take, 4),
        ];
        if ($overdraw) {
            $row['overdraw'] = true;
        }
        $deductions[] = $row;
        $lastTouchedId = $lotId;
        $lastTouchedCode = $code;
    };

    foreach ($lots as $lot) {
        if ($remainingNeed <= 1e-9) {
            break;
        }
        $avail = (float) $lot['current_qty'];
        if ($avail <= 1e-9) {
            continue;
        }
        $take = min($avail, $remainingNeed);
        $takeFromLot((int) $lot['id'], (string) $lot['supplier_code'], $take);
    }

    // Safety: never overdraw while any lot still has positive gallons (e.g. stranded
    // inactive stock that an older filter would have skipped).
    if ($remainingNeed > 1e-9) {
        $st2 = $pdo->prepare(
            'SELECT id, supplier_code, current_qty
             FROM honey_production_rows
             WHERE variant = ? AND current_qty > 0
             ORDER BY COALESCE(stock_out_date, DATE(transferred_at)) ASC, id ASC
             FOR UPDATE'
        );
        $st2->execute([$variant]);
        foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $lot) {
            if ($remainingNeed <= 1e-9) {
                break;
            }
            $avail = (float) $lot['current_qty'];
            if ($avail <= 1e-9) {
                continue;
            }
            $take = min($avail, $remainingNeed);
            $takeFromLot((int) $lot['id'], (string) $lot['supplier_code'], $take);
        }
    }

    $warning = null;
    $overdrawn = false;
    if ($remainingNeed > 1e-9) {
        // Prefer the lot we just emptied; else newest active lot; else newest lot.
        $overdrawId = $lastTouchedId;
        $overdrawCode = $lastTouchedCode;
        if ($overdrawId < 1) {
            foreach (array_reverse($lots) as $lot) {
                if ((int) ($lot['is_active'] ?? 1) === 1) {
                    $overdrawId = (int) $lot['id'];
                    $overdrawCode = (string) $lot['supplier_code'];
                    break;
                }
            }
        }
        if ($overdrawId < 1) {
            $tail = $lots[array_key_last($lots)];
            $overdrawId = (int) $tail['id'];
            $overdrawCode = (string) $tail['supplier_code'];
        }
        $takeFromLot($overdrawId, $overdrawCode, $remainingNeed, true);
        $overdrawn = true;
        $warning = sprintf(
            'Bottled volume uses %s gallon/s but production only had enough for part of that — lot may go negative.',
            round($usedGallons, 4)
        );
        $remainingNeed = 0.0;
    }

    $mlPerGallon = honey_ml_per_gallon($pdo);

    return [
        'used_gallons' => round($usedGallons, 4),
        'remaining_production_gallons' => round($totalRemainingAfter, 4),
        'production_overdrawn' => $overdrawn || $totalRemainingAfter < -1e-9,
        'warning' => $warning,
        'lot_deductions' => $deductions,
        'ml_per_gallon' => $mlPerGallon,
    ];
}

/**
 * SKU stock-in to variant POOL + FIFO production gallon deduct.
 *
 * @return array<string, mixed>
 */
function honey_packaging_pool_stock_in(
    PDO $pdo,
    int $userId,
    string $variant,
    string $size,
    int $qty
): array {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/sku_barcodes.php';

    $variant = honey_normalize_variant($variant);
    $size = honey_normalize_bottle_size($size);
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }

    $lines = [['size' => $size, 'qty' => $qty]];
    $sizeQtys = honey_aggregate_bottle_lines($lines);
    $mlPerGallon = honey_ml_per_gallon($pdo);
    $usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);
    if ($usedGallons <= 0) {
        throw new RuntimeException('Enter quantity for at least one bottle.');
    }

    $packagingDeduct = [];
    $credit = ['primary_request_id' => null, 'credited' => $qty];
    $fifo = null;
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);
        $packId = (int) $pool['id'];
        $fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);
        honey_bottle_adjust_aggregate($pdo, $packId, $size, (float) $qty);
        $packagingDeduct = honey_bottling_deduct_packaging_stocks(
            $pdo,
            $userId,
            $sizeQtys,
            HONEY_PACKAGING_POOL_BATCH
        );

        $sku = sku_barcode_fetch_honey($pdo, $variant, $size);
        if ($sku !== null) {
            $credit = sku_barcode_credit_honey_requests(
                $pdo,
                $userId,
                (int) $sku['id'],
                $variant,
                $size,
                $qty,
                $packId
            );
        }

        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'kind' => 'honey',
        'variant' => $variant,
        'size' => $size,
        'size_label' => honey_bottle_size_label($size),
        'qty' => $qty,
        'packaging_row_id' => $packId,
        'batch_no' => HONEY_PACKAGING_POOL_BATCH,
        'bottling_date' => HONEY_PACKAGING_POOL_DATE,
        'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),
        'used_gallons' => $fifo['used_gallons'],
        'remaining_production_gallons' => $fifo['remaining_production_gallons'],
        'production_overdrawn' => $fifo['production_overdrawn'],
        'warning' => $fifo['warning'],
        'ml_per_gallon' => $fifo['ml_per_gallon'],
        'lot_deductions' => $fifo['lot_deductions'],
        'packaging_deduct' => $packagingDeduct,
        'primary_request_id' => $credit['primary_request_id'] ?? null,
        'credited' => $credit['credited'] ?? $qty,
    ];
}

/**
 * @return array{variants: list<array<string,mixed>>, total_bottles: float}
 */
function honey_packaging_variant_pools_snapshot(PDO $pdo): array
{
    $variants = [];
    $grand = 0.0;
    foreach (['light', 'amber', 'dark'] as $variant) {
        $pool = honey_packaging_ensure_variant_pool($pdo, 1, $variant);
        $bySize = [
            '250ml' => (float) $pool['current_250ml'],
            '350ml' => (float) $pool['current_350ml'],
            'liter' => (float) $pool['current_liter'],
            'gallon' => (float) $pool['current_gallon'],
        ];
        $total = $bySize['250ml'] + $bySize['350ml'] + $bySize['liter'] + $bySize['gallon'];
        $grand += $total;
        $variants[] = [
            'variant' => $variant,
            'label' => ucfirst($variant),
            'packaging_row_id' => (int) $pool['id'],
            'total_bottles' => $total,
            'by_size' => $bySize,
            'current_250ml' => $bySize['250ml'],
            'current_350ml' => $bySize['350ml'],
            'current_liter' => $bySize['liter'],
            'current_gallon' => $bySize['gallon'],
            'initial_250ml' => (float) ($pool['initial_250ml'] ?? 0),
            'initial_350ml' => (float) ($pool['initial_350ml'] ?? 0),
            'initial_liter' => (float) ($pool['initial_liter'] ?? 0),
            'initial_gallon' => (float) ($pool['initial_gallon'] ?? 0),
        ];
    }

    return [
        'scope' => 'packaging_variant_pools',
        'note' => 'Finished honey on-hand by variant pool (Light / Amber / Dark).',
        'variants' => $variants,
        'total_bottles' => $grand,
    ];
}

/**
 * @return array{variants: list<array<string,mixed>>}
 */
function honey_production_variant_summary(PDO $pdo): array
{
    honey_production_sync_active_flags($pdo);

    $st = $pdo->query(
        'SELECT pr.id, pr.warehouse_row_id, pr.supplier_code, pr.arrival_date,
                COALESCE(pr.stock_out_date, DATE(pr.transferred_at)) AS stock_out_date,
                pr.daily_group_key, pr.variant, pr.current_qty, pr.initial_qty,
                COALESCE(pr.is_active, 1) AS is_active,
                pr.transferred_at, pr.created_at
         FROM honey_production_rows pr
         WHERE pr.variant IN ("light", "amber", "dark")
         ORDER BY FIELD(pr.variant, "light", "amber", "dark"),
                  (pr.current_qty > 0) DESC,
                  (pr.current_qty < 0) DESC,
                  COALESCE(pr.stock_out_date, DATE(pr.transferred_at)) DESC,
                  pr.id DESC'
    );
    $byVariant = [
        'light' => [],
        'amber' => [],
        'dark' => [],
    ];
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $v = honey_normalize_variant((string) $row['variant']);
        if (!isset($byVariant[$v])) {
            continue;
        }
        $byVariant[$v][] = $row;
    }

    $out = [];
    foreach (['light', 'amber', 'dark'] as $variant) {
        $lots = $byVariant[$variant];
        $current = 0.0;
        $initial = 0.0;
        $activeLots = 0;
        foreach ($lots as $lot) {
            $lotCurrent = (float) $lot['current_qty'];
            $lotInitial = (float) $lot['initial_qty'];
            $current += $lotCurrent;
            // Empty/overdrawn lots must not inflate the variant “of X gal” max.
            if ($lotCurrent > 1e-9) {
                $initial += $lotInitial;
            }
            if ((int) ($lot['is_active'] ?? 1) === 1) {
                $activeLots++;
            }
        }
        $out[] = [
            'variant' => $variant,
            'label' => ucfirst($variant),
            'current_qty' => $current,
            'initial_qty' => $initial,
            'lot_count' => count($lots),
            'active_lot_count' => $activeLots,
            'lots' => $lots,
        ];
    }

    return ['variants' => $out];
}

function honey_packaging_size_columns(string $size): array
{
    return match ($size) {
        'liter' => ['current_liter', 'initial_liter'],
        '250ml' => ['current_250ml', 'initial_250ml'],
        '350ml' => ['current_350ml', 'initial_350ml'],
        'gallon' => ['current_gallon', 'initial_gallon'],
        default => throw new RuntimeException('Invalid size.'),
    };
}

/** @return array<string, mixed>|null */
function honey_fetch_warehouse(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_warehouse_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function honey_fetch_production(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_production_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function honey_fetch_packaging(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/**
 * Packaging row for a production lot on a bottling date (default today).
 * When $bottlingDate is null, prefers today's row, else the newest dated row.
 *
 * @return array<string, mixed>|null
 */
function honey_fetch_packaging_by_production(
    PDO $pdo,
    int $productionRowId,
    ?string $bottlingDate = null
): ?array {
    if ($bottlingDate !== null && trim($bottlingDate) !== '') {
        $date = honey_normalize_bottling_date($bottlingDate);
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows
             WHERE production_row_id = ? AND bottling_date = ? LIMIT 1'
        );
        $st->execute([$productionRowId, $date]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    $today = date('Y-m-d');
    $st = $pdo->prepare(
        'SELECT * FROM honey_packaging_rows
         WHERE production_row_id = ?
         ORDER BY (bottling_date = ?) DESC, bottling_date DESC, id DESC
         LIMIT 1'
    );
    $st->execute([$productionRowId, $today]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function honey_warehouse_stock_in(PDO $pdo, int $userId, int $supplierId, string $arrivalDate, array $variants): array
{
    if ($supplierId < 1) {
        throw new RuntimeException('Select a supplier from the list.');
    }
    if (!$variants) {
        throw new RuntimeException('Add at least one variant row.');
    }
    $supplier = supplier_require_honey_active($pdo, $supplierId);
    $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
    $code = honey_supplier_code($supplierName, $arrivalDate);
    $created = [];
    $hasPricingCols = honey_warehouse_has_pricing_columns($pdo);
    if ($hasPricingCols) {
        $ins = $pdo->prepare(
            'INSERT INTO honey_warehouse_rows (
                supplier_id, supplier_name, supplier_code, arrival_date, variant,
                current_qty, initial_qty, unit_cost_per_gallon, supplier_price_id, cost_override, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, 0, ?)'
        );
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO honey_warehouse_rows (supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
    }

    $variantsTouched = [];
    foreach ($variants as $row) {
        $variant = honey_normalize_variant((string) ($row['variant'] ?? ''));
        $qty = (float) ($row['qty'] ?? 0);
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
        $ins->execute([$supplierId, $supplierName, $code, $arrivalDate, $variant, $qty, $qty, $userId]);
        $wid = (int) $pdo->lastInsertId();
        $pricing = ['priced' => false, 'unit_cost_per_gallon' => null, 'supplier_price_id' => null];
        if ($hasPricingCols) {
            honey_inventory_pricing();
            $pricing = honey_pricing_snapshot_warehouse_row($pdo, $wid);
            if ($pricing['priced']) {
                $variantsTouched[$variant] = true;
            }
        }
        $created[] = [
            'id' => $wid,
            'supplier_code' => $code,
            'variant' => $variant,
            'current_qty' => $qty,
            'initial_qty' => $qty,
            'unit_cost_per_gallon' => $pricing['unit_cost_per_gallon'],
            'pricing_status' => $pricing['priced'] ? 'priced' : 'pending',
        ];
    }

    if ($hasPricingCols) {
        honey_inventory_pricing();
        honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);
    }

    return ['supplier_code' => $code, 'rows' => $created];
}

function honey_warehouse_has_pricing_columns(PDO $pdo): bool
{
    return honey_warehouse_unit_cost_column($pdo) !== null;
}

/** @return 'unit_cost_per_gallon'|'unit_cost_per_liter'|null */
function honey_warehouse_unit_cost_column(PDO $pdo): ?string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache === '' ? null : $cache;
    }
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    foreach (['unit_cost_per_gallon', 'unit_cost_per_liter'] as $col) {
        $st->execute(['honey_warehouse_rows', $col]);
        if ($st->fetchColumn()) {
            $cache = $col;

            return $col;
        }
    }
    $cache = '';

    return null;
}

function honey_warehouse_normalize_cost_row(array $row): array
{
    if (!isset($row['unit_cost_per_gallon']) && isset($row['unit_cost_per_liter'])) {
        $row['unit_cost_per_gallon'] = $row['unit_cost_per_liter'];
        unset($row['unit_cost_per_liter']);
    }

    return $row;
}

/**
 * Production lot merge key: one open lot per supplier/code/arrival/variant.
 * Stock-outs on later days add gallons to the same lot (refill), not a new row.
 */
function honey_production_daily_group_key(
    int $supplierId,
    string $supplierCode,
    string $arrivalDate,
    string $variant,
    ?string $stockOutDate = null
): string {
    // Stock-out date is intentionally not part of the merge key (cross-day refill).
    if ($stockOutDate !== null && $stockOutDate === '') {
        // no-op: keep optional arg for older callers
    }

    return $supplierId . '|' . $supplierCode . '|' . $arrivalDate . '|' . $variant;
}

/**
 * Upsert production gallons onto one lot for supplier/code/arrival/variant.
 * Same-day and later-day stock-outs refill the same row. Caller must already
 * hold a transaction and must have deducted warehouse qty.
 *
 * @param list<array{warehouse_row_id:int, quantity:float}> $sources
 * @return int production_row_id
 */
function honey_production_upsert_daily_lot(
    PDO $pdo,
    int $userId,
    int $supplierId,
    string $supplierCode,
    string $arrivalDate,
    string $variant,
    string $stockOutDate,
    float $quantity,
    int $primaryWarehouseRowId,
    array $sources
): int {
    $quantity = round($quantity, 2);
    if ($quantity <= 0) {
        throw new RuntimeException('Stock-out quantity must be greater than zero.');
    }
    $supplierCode = strtoupper(trim($supplierCode));
    $variant = honey_normalize_variant($variant);
    $lotKey = honey_production_daily_group_key(
        $supplierId,
        $supplierCode,
        $arrivalDate,
        $variant
    );

    $prod = $pdo->prepare(
        'SELECT * FROM honey_production_rows WHERE daily_group_key = ? LIMIT 1 FOR UPDATE'
    );
    $prod->execute([$lotKey]);
    $production = $prod->fetch(PDO::FETCH_ASSOC);

    if ($production === false) {
        // Absorb legacy dated keys / same-day splits / NULL keys for this supplier lot.
        // Prefer overdrawn lots so a restock refills the shortfall row first.
        $legacy = $pdo->prepare(
            'SELECT * FROM honey_production_rows
             WHERE supplier_code = ? AND arrival_date = ? AND variant = ?
             ORDER BY (current_qty < 0) DESC, id ASC
             FOR UPDATE'
        );
        $legacy->execute([$supplierCode, $arrivalDate, $variant]);
        $matches = $legacy->fetchAll(PDO::FETCH_ASSOC);
        $production = $matches[0] ?? false;
        if ($production !== false) {
            $productionId = (int) $production['id'];
            $extraCurrent = 0.0;
            $extraInitial = 0.0;
            $latestStockOut = $stockOutDate;
            for ($i = 1, $n = count($matches); $i < $n; $i++) {
                $extra = $matches[$i];
                $extraId = (int) $extra['id'];
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
                $pkConflict->execute([$productionId, $extraId]);
                if ($pkConflict->fetchColumn() !== false) {
                    // Leave this day-split row alone; still refill the keeper below.
                    continue;
                }
                $extraCurrent += (float) $extra['current_qty'];
                $extraInitial += (float) $extra['initial_qty'];
                $extraOut = (string) ($extra['stock_out_date'] ?? '');
                if ($extraOut !== '' && ($latestStockOut === '' || $extraOut > $latestStockOut)) {
                    $latestStockOut = $extraOut;
                }
                $pdo->prepare(
                    'UPDATE honey_warehouse_stock_out_events SET production_row_id = ? WHERE production_row_id = ?'
                )->execute([$productionId, $extraId]);
                $pdo->prepare(
                    'UPDATE honey_packaging_rows SET production_row_id = ? WHERE production_row_id = ?'
                )->execute([$productionId, $extraId]);
                $pdo->prepare('DELETE FROM honey_production_rows WHERE id = ?')->execute([$extraId]);
            }
            if ($stockOutDate !== '' && ($latestStockOut === '' || $stockOutDate > $latestStockOut)) {
                $latestStockOut = $stockOutDate;
            }
            $pdo->prepare(
                'UPDATE honey_production_rows
                 SET current_qty = current_qty + ? + ?,
                     initial_qty = ?,
                     stock_out_date = ?,
                     daily_group_key = ?
                 WHERE id = ?'
            )->execute([
                $quantity,
                $extraCurrent,
                honey_production_refill_initial_qty(
                    (float) $production['current_qty'],
                    (float) $production['initial_qty'],
                    $quantity,
                    $extraCurrent,
                    $extraInitial
                ),
                $latestStockOut !== '' ? $latestStockOut : $stockOutDate,
                $lotKey,
                $productionId,
            ]);
            honey_production_clear_depleted_and_activate($pdo, $productionId);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO honey_production_rows
                    (warehouse_row_id, supplier_code, arrival_date, stock_out_date, daily_group_key,
                     variant, current_qty, initial_qty, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $primaryWarehouseRowId,
                $supplierCode,
                $arrivalDate,
                $stockOutDate,
                $lotKey,
                $variant,
                $quantity,
                $quantity,
                $userId,
            ]);
            $productionId = (int) $pdo->lastInsertId();
        }
    } else {
        $productionId = (int) $production['id'];
        // Fold any leftover same-lot duplicates (dated keys) into this canonical row.
        $dupes = $pdo->prepare(
            'SELECT * FROM honey_production_rows
             WHERE supplier_code = ? AND arrival_date = ? AND variant = ? AND id <> ?
             FOR UPDATE'
        );
        $dupes->execute([$supplierCode, $arrivalDate, $variant, $productionId]);
        $extraCurrent = 0.0;
        $extraInitial = 0.0;
        foreach ($dupes->fetchAll(PDO::FETCH_ASSOC) as $extra) {
            $extraId = (int) $extra['id'];
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
            $pkConflict->execute([$productionId, $extraId]);
            if ($pkConflict->fetchColumn() !== false) {
                continue;
            }
            $extraCurrent += (float) $extra['current_qty'];
            $extraInitial += (float) $extra['initial_qty'];
            $pdo->prepare(
                'UPDATE honey_warehouse_stock_out_events SET production_row_id = ? WHERE production_row_id = ?'
            )->execute([$productionId, $extraId]);
            $pdo->prepare(
                'UPDATE honey_packaging_rows SET production_row_id = ? WHERE production_row_id = ?'
            )->execute([$productionId, $extraId]);
            $pdo->prepare('DELETE FROM honey_production_rows WHERE id = ?')->execute([$extraId]);
        }
        $pdo->prepare(
            'UPDATE honey_production_rows
             SET current_qty = current_qty + ? + ?,
                 initial_qty = ?,
                 stock_out_date = ?
             WHERE id = ?'
        )->execute([
            $quantity,
            $extraCurrent,
            honey_production_refill_initial_qty(
                (float) $production['current_qty'],
                (float) $production['initial_qty'],
                $quantity,
                $extraCurrent,
                $extraInitial
            ),
            $stockOutDate,
            $productionId,
        ]);
        honey_production_clear_depleted_and_activate($pdo, $productionId);
    }

    $event = $pdo->prepare(
        'INSERT INTO honey_warehouse_stock_out_events
            (warehouse_row_id, production_row_id, supplier_id, supplier_code, arrival_date,
             variant, quantity_gallons, stock_out_date, stocked_out_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($sources as $source) {
        $event->execute([
            (int) $source['warehouse_row_id'],
            $productionId,
            $supplierId > 0 ? $supplierId : null,
            $supplierCode,
            $arrivalDate,
            $variant,
            round((float) $source['quantity'], 2),
            $stockOutDate,
            $userId,
        ]);
    }

    return $productionId;
}

function honey_warehouse_stock_out(PDO $pdo, int $userId, int $warehouseRowId, float $qty): array
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    $current = (float) $wh['current_qty'];
    if ($qty <= 0 || $qty > $current) {
        throw new RuntimeException('Invalid quantity for stock-out.');
    }
    $qty = round($qty, 2);
    $supplierId = (int) ($wh['supplier_id'] ?? 0);
    $supplierCode = strtoupper(trim((string) $wh['supplier_code']));
    $arrivalDate = (string) $wh['arrival_date'];
    $variant = honey_normalize_variant((string) $wh['variant']);
    $stockOutDate = date('Y-m-d');

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE honey_warehouse_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?'
        );
        $upd->execute([$qty, $warehouseRowId, $qty]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Could not deduct warehouse quantity.');
        }
        $prodId = honey_production_upsert_daily_lot(
            $pdo,
            $userId,
            $supplierId,
            $supplierCode,
            $arrivalDate,
            $variant,
            $stockOutDate,
            $qty,
            $warehouseRowId,
            [['warehouse_row_id' => $warehouseRowId, 'quantity' => $qty]]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'production_row_id' => $prodId,
        'qty' => $qty,
        'stock_out_date' => $stockOutDate,
        'merged' => true,
    ];
}

/**
 * Bulk B2B sale: deduct warehouse gallons only (no production row).
 *
 * @return array{warehouse_row_id:int, qty:float, supplier_code:string}
 */
function honey_warehouse_bulk_deduct(PDO $pdo, int $warehouseRowId, float $qty): array
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be positive.');
    }
    $upd = $pdo->prepare(
        'UPDATE honey_warehouse_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?'
    );
    $upd->execute([$qty, $warehouseRowId, $qty]);
    if ($upd->rowCount() < 1) {
        $code = (string) ($wh['supplier_code'] ?? '');
        throw new RuntimeException(
            "Not enough warehouse stock for supplier code {$code} (need {$qty} gal)."
        );
    }

    return [
        'warehouse_row_id' => $warehouseRowId,
        'qty' => $qty,
        'supplier_code' => (string) ($wh['supplier_code'] ?? ''),
    ];
}

/**
 * Restore warehouse gallons (bulk order void).
 */
function honey_warehouse_bulk_restore(PDO $pdo, int $warehouseRowId, float $qty): void
{
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be positive.');
    }
    $upd = $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = current_qty + ? WHERE id = ?');
    $upd->execute([$qty, $warehouseRowId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Could not restore warehouse quantity.');
    }
}

/**
 * Move bottled product from production to packaging.
 * Gallons deducted = auto from bottle line volumes. One packaging row per production row.
 *
 * @param list<array{size:string, qty:float}> $lines
 */
function honey_production_stock_out(PDO $pdo, int $userId, int $productionRowId, array $lines): array
{
    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    if (isset($pr['is_active']) && (int) $pr['is_active'] === 0) {
        throw new RuntimeException('This production lot is inactive. An executive must reactivate it before scanning.');
    }
    if (!$lines) {
        throw new RuntimeException('Add at least one bottle line with quantity.');
    }

    $sizeQtys = honey_aggregate_bottle_lines($lines);
    $mlPerGallon = honey_ml_per_gallon($pdo);
    $usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);
    if ($usedGallons <= 0) {
        throw new RuntimeException('Enter quantity for at least one bottle.');
    }

    $available = (float) $pr['current_qty'];
    if ($usedGallons > $available + 1e-9) {
        $usedFriendly = round($usedGallons, 1);
        $availFriendly = round($available, 1);
        throw new RuntimeException(
            "Bottled volume uses {$usedFriendly} gallon/s but only {$availFriendly} gallon/s remain in production."
        );
    }

    $liter = $sizeQtys['liter'];
    $ml250 = $sizeQtys['250ml'];
    $ml350 = $sizeQtys['350ml'];
    $gallon = $sizeQtys['gallon'];

    $existing = honey_fetch_packaging_by_production($pdo, $productionRowId, date('Y-m-d'));
    $isNew = $existing === null;
    if ($isNew) {
        $bottlingDate = date('Y-m-d');
        $prior = honey_fetch_packaging_by_production($pdo, $productionRowId);
        if ($prior !== null) {
            // Same production lot bottled on a later day keeps the same batch label.
            $batchNo = (string) $prior['batch_no'];
        } else {
            $batchNo = honey_unique_packaging_batch_no(
                $pdo,
                honey_batch_no((string) $pr['supplier_code'], $bottlingDate),
                (string) $pr['variant'],
                $bottlingDate
            );
        }
    } else {
        $bottlingDate = (string) $existing['bottling_date'];
        $batchNo = (string) $existing['batch_no'];
    }

    $packagingDeduct = [];
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE honey_production_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?'
        );
        $upd->execute([$usedGallons, $productionRowId, $usedGallons]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Could not deduct production quantity.');
        }
        honey_production_sync_depleted_at($pdo, $productionRowId);

        if ($isNew) {
            $ins = $pdo->prepare(
                'INSERT INTO honey_packaging_rows (
                    production_row_id, supplier_code, batch_no, bottling_date, variant,
                    current_liter, initial_liter, current_250ml, initial_250ml,
                    current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
                 ) VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
            );
            $ins->execute([
                $productionRowId,
                $pr['supplier_code'],
                $batchNo,
                $bottlingDate,
                $pr['variant'],
                $userId,
            ]);
            $packId = (int) $pdo->lastInsertId();
        } else {
            $packId = (int) $existing['id'];
        }

        $packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $remaining = $available - $usedGallons;

    return [
        'packaging_row_id' => $packId,
        'batch_no' => $batchNo,
        'bottling_date' => $bottlingDate,
        'used_gallons' => round($usedGallons, 4),
        'remaining_production_gallons' => round(max(0, $remaining), 4),
        'is_new_packaging_row' => $isNew,
        'packaging_deduct' => $packagingDeduct ?? [],
        'ml_per_gallon' => $mlPerGallon,
    ];
}

/**
 * Ensure packaging row exists for a production lot on today's bottling date.
 * A gallon lot bottled across multiple calendar days gets one row per day
 * (same batch_no / production_row_id, different bottling_date).
 *
 * @return array{packaging_row_id:int, batch_no:string, bottling_date:string, is_new:bool, production:array<string,mixed>}
 */
function honey_production_ensure_packaging_row(PDO $pdo, int $userId, int $productionRowId): array
{
    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    $bottlingDate = date('Y-m-d');
    $existing = honey_fetch_packaging_by_production($pdo, $productionRowId, $bottlingDate);
    if ($existing !== null) {
        return [
            'packaging_row_id' => (int) $existing['id'],
            'batch_no' => (string) $existing['batch_no'],
            'bottling_date' => (string) $existing['bottling_date'],
            'is_new' => false,
            'production' => $pr,
        ];
    }

    $prior = honey_fetch_packaging_by_production($pdo, $productionRowId);
    if ($prior !== null) {
        $batchNo = (string) $prior['batch_no'];
    } else {
        $batchNo = honey_unique_packaging_batch_no(
            $pdo,
            honey_batch_no((string) $pr['supplier_code'], $bottlingDate),
            (string) $pr['variant'],
            $bottlingDate
        );
    }
    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
    );
    $ins->execute([
        $productionRowId,
        $pr['supplier_code'],
        $batchNo,
        $bottlingDate,
        $pr['variant'],
        $userId,
    ]);

    return [
        'packaging_row_id' => (int) $pdo->lastInsertId(),
        'batch_no' => $batchNo,
        'bottling_date' => $bottlingDate,
        'is_new' => true,
        'production' => $pr,
    ];
}

/**
 * Deduct production gallons for bottled volume (caller must be in a transaction when needed).
 * Allows negative remaining so ops can finish bottling while the shortfall stays visible.
 */
function honey_production_deduct_gallons(PDO $pdo, int $productionRowId, float $usedGallons): void
{
    if ($usedGallons <= 0) {
        throw new RuntimeException('Bottle volume must be greater than zero.');
    }
    $upd = $pdo->prepare(
        'UPDATE honey_production_rows
         SET current_qty = current_qty - ?,
             initial_qty = CASE
                 WHEN (current_qty - ?) <= 1e-9 THEN 0
                 ELSE initial_qty
             END
         WHERE id = ?'
    );
    $upd->execute([$usedGallons, $usedGallons, $productionRowId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Could not deduct production quantity.');
    }
    honey_production_sync_depleted_at($pdo, $productionRowId);
}

/**
 * Stamp or clear depleted_at from the row's current_qty (phase 88).
 */
function honey_production_sync_depleted_at(PDO $pdo, int $productionRowId): void
{
    if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {
        return;
    }
    $pdo->prepare(
        'UPDATE honey_production_rows
         SET depleted_at = CASE
             WHEN current_qty <= 1e-9 THEN COALESCE(depleted_at, NOW())
             ELSE NULL
         END
         WHERE id = ?'
    )->execute([$productionRowId]);
}

/**
 * After gallons are added back via warehouse stock-out: clear depleted_at and reactivate.
 */
function honey_production_clear_depleted_and_activate(PDO $pdo, int $productionRowId): void
{
    if ($productionRowId < 1) {
        return;
    }
    if (honey_production_has_depleted_at($pdo)) {
        $pdo->prepare(
            'UPDATE honey_production_rows
             SET depleted_at = NULL, is_active = 1
             WHERE id = ?'
        )->execute([$productionRowId]);
    } else {
        $pdo->prepare(
            'UPDATE honey_production_rows SET is_active = 1 WHERE id = ?'
        )->execute([$productionRowId]);
    }
}

/**
 * Clear depleted_at only (e.g. executive set qty > 0 without forcing active).
 */
function honey_production_clear_depleted_at(PDO $pdo, int $productionRowId): void
{
    if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {
        return;
    }
    $pdo->prepare(
        'UPDATE honey_production_rows SET depleted_at = NULL WHERE id = ?'
    )->execute([$productionRowId]);
}

/**
 * Next stock-out baseline after an empty/overdrawn lot: new fill becomes the cycle max.
 */
function honey_production_refill_initial_qty(float $priorCurrent, float $priorInitial, float $addQty, float $extraCurrent = 0.0, float $extraInitial = 0.0): float
{
    $addQty = round($addQty, 2);
    $extraCurrent = round($extraCurrent, 2);
    $extraInitial = round($extraInitial, 2);
    if ($priorCurrent <= 1e-9) {
        $newCurrent = round($priorCurrent + $addQty + $extraCurrent, 2);
        // Fresh cycle: "of X" = gallons just stocked (cover overdraw shortfall in the baseline).
        return max($addQty + max(0.0, $extraCurrent), max(0.0, $newCurrent), 0.0);
    }

    return round($priorInitial + $addQty + $extraInitial, 2);
}

/**
 * Stock finished bottles into packaging from a production lot (deduct gallons + credit aggregates).
 *
 * @return array<string, mixed>
 */
function honey_production_bottle_stock_in(
    PDO $pdo,
    int $userId,
    int $productionRowId,
    string $variant,
    string $size,
    int $qty
): array {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/sku_barcodes.php';

    $variant = honey_normalize_variant($variant);
    $size = honey_normalize_bottle_size($size);
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }

    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    if (isset($pr['is_active']) && (int) $pr['is_active'] === 0) {
        throw new RuntimeException('This production lot is inactive. An executive must reactivate it before scanning.');
    }
    if (honey_normalize_variant((string) $pr['variant']) !== $variant) {
        throw new RuntimeException('SKU variant does not match the selected production lot.');
    }

    $lines = [['size' => $size, 'qty' => $qty]];
    $sizeQtys = honey_aggregate_bottle_lines($lines);
    $mlPerGallon = honey_ml_per_gallon($pdo);
    $usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);
    if ($usedGallons <= 0) {
        throw new RuntimeException('Enter quantity for at least one bottle.');
    }

    $available = (float) $pr['current_qty'];
    $overdrawn = $usedGallons > $available + 1e-9;
    $warning = null;
    if ($overdrawn) {
        $usedFriendly = round($usedGallons, 4);
        $availFriendly = round($available, 4);
        $warning = "Bottled volume uses {$usedFriendly} gallon/s but only {$availFriendly} gallon/s remain — lot may go negative.";
    }

    $packagingDeduct = [];
    $credit = ['primary_request_id' => null, 'credited' => $qty];
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);
        $packId = (int) $ensured['packaging_row_id'];
        $batchNo = (string) $ensured['batch_no'];

        honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);
        honey_bottle_adjust_aggregate($pdo, $packId, $size, (float) $qty);
        $packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);

        $sku = sku_barcode_fetch_honey($pdo, $variant, $size);
        if ($sku !== null) {
            $credit = sku_barcode_credit_honey_requests(
                $pdo,
                $userId,
                (int) $sku['id'],
                $variant,
                $size,
                $qty,
                $packId
            );
        }

        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $remaining = $available - $usedGallons;

    return [
        'kind' => 'honey',
        'variant' => $variant,
        'size' => $size,
        'size_label' => honey_bottle_size_label($size),
        'qty' => $qty,
        'packaging_row_id' => $packId,
        'batch_no' => $batchNo,
        'bottling_date' => (string) ($ensured['bottling_date'] ?? date('Y-m-d')),
        'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),
        'primary_request_id' => $credit['primary_request_id'] ?? null,
        'used_gallons' => round($usedGallons, 4),
        'remaining_production_gallons' => round($remaining, 4),
        'production_overdrawn' => $overdrawn || $remaining < -1e-9,
        'warning' => $warning,
        'is_new_packaging_row' => !empty($ensured['is_new']),
        'packaging_deduct' => $packagingDeduct,
        'ml_per_gallon' => $mlPerGallon,
        'production_row_id' => $productionRowId,
    ];
}

/**
 * Deduct production gallons for a unique bottle sticker already tied to that lot's packaging.
 *
 * @return array{used_gallons:float, remaining_production_gallons:float, production_overdrawn:bool, warning:?string, ml_per_gallon:float, packaging_row_id:int}
 */
function honey_production_deduct_for_unique_stock_in(
    PDO $pdo,
    int $userId,
    int $productionRowId,
    int $packagingRowId,
    string $size
): array {
    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    $pk = honey_fetch_packaging($pdo, $packagingRowId);
    if ($pk === null) {
        throw new RuntimeException('Packaging row not found.');
    }
    if ((int) ($pk['production_row_id'] ?? 0) !== $productionRowId) {
        throw new RuntimeException('This barcode belongs to a different production lot. Select the matching lot.');
    }

    $size = honey_normalize_bottle_size($size);
    $mlPerGallon = honey_ml_per_gallon($pdo);
    $usedGallons = honey_lines_to_gallons([['size' => $size, 'qty' => 1]], $mlPerGallon);
    $available = (float) $pr['current_qty'];
    $remaining = $available - $usedGallons;
    $overdrawn = $usedGallons > $available + 1e-9;
    $warning = null;
    if ($overdrawn) {
        $usedFriendly = round($usedGallons, 4);
        $availFriendly = round($available, 4);
        $warning = "Bottled volume uses {$usedFriendly} gallon/s but only {$availFriendly} gallon/s remain — lot may go negative.";
    }

    honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);
    honey_bottling_deduct_packaging_stocks(
        $pdo,
        $userId,
        honey_aggregate_bottle_lines([['size' => $size, 'qty' => 1]]),
        (string) $pk['batch_no']
    );

    return [
        'used_gallons' => round($usedGallons, 4),
        'remaining_production_gallons' => round($remaining, 4),
        'production_overdrawn' => $overdrawn || $remaining < -1e-9,
        'warning' => $warning,
        'ml_per_gallon' => $mlPerGallon,
        'packaging_row_id' => $packagingRowId,
    ];
}

/**
 * Deduct per-unit honey packaging from stocks.on_hand when bottling.
 *
 * @param array<string, float> $sizeQtys
 * @return list<array<string, mixed>>
 */
function honey_bottling_deduct_packaging_stocks(PDO $pdo, int $userId, array $sizeQtys, string $label): array
{
    if (!honey_packaging_deduct_enabled($pdo)) {
        return [];
    }
    if (!material_inventory_has_pool_columns($pdo)) {
        return [];
    }

    $deptSt = $pdo->query("SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1");
    $deptId = $deptSt ? (int) $deptSt->fetchColumn() : 0;
    if ($deptId < 1) {
        return [];
    }

    $applied = [];
    $totalBottles = 0.0;
    foreach (HONEY_SIZES as $size) {
        $qty = (float) ($sizeQtys[$size] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $totalBottles += $qty;
        $bottleName = catalog_honey_bottle_stock_name($size);
        $st = $pdo->prepare(
            'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL AND production_deduct = 1 LIMIT 1'
        );
        $st->execute([$deptId, $bottleName]);
        $stockId = (int) $st->fetchColumn();
        if ($stockId < 1) {
            continue;
        }
        $res = dept_packaging_deduct($pdo, $stockId, $qty, $userId, 'bottling: ' . substr($label, 0, 100));
        $applied[] = [
            'stock_name' => $bottleName,
            'qty' => $qty,
            'on_hand' => $res['on_hand'],
        ];
    }

    if ($totalBottles > 0) {
        $st = $pdo->prepare(
            'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL AND production_deduct = 1 LIMIT 1'
        );
        $st->execute([$deptId, CATALOG_HONEY_STICKER_STOCK_NAME]);
        $stickerId = (int) $st->fetchColumn();
        if ($stickerId > 0) {
            $res = dept_packaging_deduct($pdo, $stickerId, $totalBottles, $userId, 'bottling: ' . substr($label, 0, 100));
            $applied[] = [
                'stock_name' => CATALOG_HONEY_STICKER_STOCK_NAME,
                'qty' => $totalBottles,
                'on_hand' => $res['on_hand'],
            ];
        }
    }

    return $applied;
}

function honey_packaging_stock_out_sold(PDO $pdo, int $userId, int $packagingRowId, array $sizeQtys, string $saleType): array
{
    $pk = honey_fetch_packaging($pdo, $packagingRowId);
    if (!$pk) {
        throw new RuntimeException('Packaging row not found.');
    }
    if (!in_array($saleType, ['walk_in', 'parcel'], true)) {
        throw new RuntimeException('Invalid sale type.');
    }

    $deducts = [];
    foreach (HONEY_SIZES as $size) {
        $q = (float) ($sizeQtys[$size] ?? 0);
        if ($q <= 0) {
            continue;
        }
        [$curCol] = honey_packaging_size_columns($size);
        $avail = (float) $pk[$curCol];
        if ($q > $avail) {
            throw new RuntimeException("Not enough {$size} available.");
        }
        $deducts[$size] = $q;
    }
    if (!$deducts) {
        throw new RuntimeException('Select at least one size quantity to sell.');
    }

    $pdo->beginTransaction();
    try {
        $sets = [];
        $params = [];
        foreach ($deducts as $size => $q) {
            [$curCol] = honey_packaging_size_columns($size);
            $sets[] = "{$curCol} = {$curCol} - ?";
            $params[] = $q;
        }
        $params[] = $packagingRowId;
        $sql = 'UPDATE honey_packaging_rows SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);

        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (source, packaging_row_id, batch_no, variant, qty_liter, qty_250ml, qty_350ml, qty_gallon, sale_type, sold_by)
             VALUES ("packaging", ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $packagingRowId,
            $pk['batch_no'],
            $pk['variant'],
            $deducts['liter'] ?? 0,
            $deducts['250ml'] ?? 0,
            $deducts['350ml'] ?? 0,
            $deducts['gallon'] ?? 0,
            $saleType,
            $userId,
        ]);
        $saleId = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['sale_id' => $saleId, 'deducted' => $deducts];
}

function honey_apply_correction(PDO $pdo, string $stage, string $rowTable, int $rowId, string $action, float $amount, ?string $size): void
{
    honey_apply_correction_request($pdo, [
        'correction_type' => 'qty_delta',
        'stage' => $stage,
        'row_table' => $rowTable,
        'row_id' => $rowId,
        'action' => $action,
        'amount' => $amount,
        'size' => $size,
        'new_value' => null,
    ]);
}

/** @param array<string, mixed> $req */
function honey_apply_correction_request(PDO $pdo, array $req): void
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $stage = (string) ($req['stage'] ?? '');
    $rowTable = (string) ($req['row_table'] ?? '');
    $rowId = (int) ($req['row_id'] ?? 0);
    $newValue = isset($req['new_value']) ? trim((string) $req['new_value']) : '';

    if ($type === 'qty_delta') {
        $action = (string) ($req['action'] ?? '');
        $amount = (float) ($req['amount'] ?? 0);
        $size = $req['size'] !== null ? (string) $req['size'] : null;
        $delta = $action === 'add' ? $amount : -$amount;
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }
        if ($rowTable === 'honey_warehouse_rows' && $stage === 'warehouse') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = current_qty + ? WHERE id = ?')->execute([$delta, $rowId]);

            return;
        }
        if ($rowTable === 'honey_production_rows' && $stage === 'production') {
            $pdo->prepare(
                'UPDATE honey_production_rows
                 SET current_qty = current_qty + ?,
                     initial_qty = CASE
                         WHEN (current_qty + ?) <= 1e-9 THEN 0
                         ELSE initial_qty
                     END
                 WHERE id = ?'
            )->execute([$delta, $delta, $rowId]);
            honey_production_sync_depleted_at($pdo, $rowId);
            $pr = honey_fetch_production($pdo, $rowId);
            if ($pr && (float) $pr['current_qty'] > 1e-9) {
                honey_production_clear_depleted_at($pdo, $rowId);
            }

            return;
        }
        if ($rowTable === 'honey_packaging_rows' && $stage === 'packaging') {
            if ($size === null || $size === '') {
                throw new RuntimeException('Size required for packaging correction.');
            }
            [$curCol] = honey_packaging_size_columns($size);
            $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = {$curCol} + ? WHERE id = ?")->execute([$delta, $rowId]);

            return;
        }
        if ($rowTable === 'dept_inventory_rows' && $stage === 'dept') {
            $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = current_qty + ? WHERE id = ?')->execute([$delta, $rowId]);

            return;
        }
        throw new RuntimeException('Unsupported correction target.');
    }

    if ($type === 'set_qty') {
        $qty = (float) $newValue;
        if ($qty < 0) {
            throw new RuntimeException('Quantity cannot be negative.');
        }
        if ($rowTable === 'honey_warehouse_rows') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);

            return;
        }
        if ($rowTable === 'honey_production_rows') {
            $pdo->prepare('UPDATE honey_production_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            honey_production_sync_depleted_at($pdo, $rowId);
            if ($qty > 1e-9) {
                honey_production_clear_depleted_at($pdo, $rowId);
            }

            return;
        }
        if ($rowTable === 'dept_inventory_rows') {
            $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);

            return;
        }
        throw new RuntimeException('Set quantity not supported for this row.');
    }

    if ($type === 'variant') {
        $variant = honey_normalize_variant($newValue);
        if ($rowTable === 'honey_warehouse_rows') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);

            return;
        }
        if ($rowTable === 'honey_production_rows') {
            $pdo->prepare('UPDATE honey_production_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            $pdo->prepare(
                'UPDATE honey_packaging_rows pk
                 INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
                 SET pk.variant = ? WHERE pr.id = ?'
            )->execute([$variant, $rowId]);

            return;
        }
        if ($rowTable === 'honey_packaging_rows') {
            $pdo->prepare('UPDATE honey_packaging_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);

            return;
        }
        throw new RuntimeException('Variant change not supported for this row.');
    }

    if ($type === 'supplier') {
        if ($rowTable !== 'honey_warehouse_rows') {
            throw new RuntimeException('Supplier can only be changed on warehouse rows.');
        }
        $supplierId = (int) $newValue;
        if ($supplierId < 1) {
            throw new RuntimeException('Invalid supplier.');
        }
        $supplier = supplier_require_honey_active($pdo, $supplierId);
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            throw new RuntimeException('Warehouse row not found.');
        }
        $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
        $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
        $pdo->prepare(
            'UPDATE honey_warehouse_rows SET supplier_id = ?, supplier_name = ?, supplier_code = ? WHERE id = ?'
        )->execute([$supplierId, $supplierName, $code, $rowId]);
        honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);

        return;
    }

    if ($type === 'packaging_size') {
        if ($rowTable !== 'honey_packaging_rows') {
            throw new RuntimeException('Packaging size correction requires packaging row.');
        }
        $parts = explode(':', $newValue, 2);
        $size = $parts[0] ?? '';
        $qty = (float) ($parts[1] ?? 0);
        if ($qty < 0) {
            throw new RuntimeException('Quantity cannot be negative.');
        }
        [$curCol, $initCol] = honey_packaging_size_columns($size);
        $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")->execute([$qty, $qty, $rowId]);

        return;
    }

    throw new RuntimeException('Unknown correction type.');
}

function honey_cascade_warehouse_metadata(PDO $pdo, int $warehouseRowId, ?int $supplierId, ?string $variant): void
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        return;
    }
    $code = (string) $wh['supplier_code'];
    $variantVal = (string) $wh['variant'];
    if ($supplierId !== null) {
        $supplier = supplier_fetch($pdo, $supplierId);
        if ($supplier) {
            $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
            $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
        }
    }
    if ($variant !== null) {
        $variantVal = $variant;
    }
    $pdo->prepare(
        'UPDATE honey_production_rows SET supplier_code = ?, variant = ? WHERE warehouse_row_id = ?'
    )->execute([$code, $variantVal, $warehouseRowId]);
    $pdo->prepare(
        'UPDATE honey_packaging_rows pk
         INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
         SET pk.supplier_code = ?, pk.variant = ?
         WHERE pr.warehouse_row_id = ?'
    )->execute([$code, $variantVal, $warehouseRowId]);
}

function honey_log_edit(
    PDO $pdo,
    int $userId,
    string $rowTable,
    int $rowId,
    string $field,
    ?string $old,
    ?string $new,
    ?string $source = 'executive_edit',
    ?string $reason = null
): void {
    inventory_edit_log_write($pdo, $userId, $rowTable, $rowId, $field, $old, $new, $source, $reason);
}

function honey_traceability(PDO $pdo, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return ['matches' => []];
    }

    $matches = [];
    if (preg_match('/^([A-Z0-9]{6}|[A-Z]{2,10}\d{6}-\d{6}-\d{5})$/i', $query)) {
        require_once __DIR__ . '/honey_bottle_barcodes.php';
        try {
            $lookup = honey_bottle_lookup($pdo, strtoupper($query));
            $packId = (int) ($lookup['packaging_row_id'] ?? 0);
            if ($packId > 0) {
                $pk = honey_fetch_packaging($pdo, $packId);
                if ($pk) {
                    $chain = honey_build_trace_chain($pdo, $pk);
                    $chain['bottle'] = $lookup;
                    $matches[] = $chain;
                }
            }
        } catch (Throwable) {
            /* fall through */
        }

        return ['matches' => $matches];
    }
    if (preg_match('/^[A-Z]{2,10}\d{6}-\d{6}$/i', $query)) {
        $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE batch_no = ? LIMIT 20');
        $st->execute([strtoupper($query)]);
        foreach ($st->fetchAll() as $pk) {
            $matches[] = honey_build_trace_chain($pdo, $pk);
        }

        return ['matches' => $matches];
    }
    if (preg_match('/^[A-Z]{2,10}\d{6}$/i', $query)) {
        $code = strtoupper($query);
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows
             WHERE supplier_code = ? OR batch_no = ? OR batch_no LIKE ?
             LIMIT 50'
        );
        $st->execute([$code, $code, $code . '-%']);
        foreach ($st->fetchAll() as $pk) {
            $matches[] = honey_build_trace_chain($pdo, $pk);
        }

        return ['matches' => $matches];
    }

    $st = $pdo->prepare(
        'SELECT pk.* FROM honey_packaging_rows pk
         INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
         INNER JOIN honey_warehouse_rows wh ON wh.id = pr.warehouse_row_id
         WHERE wh.supplier_name LIKE ? LIMIT 30'
    );
    $st->execute(['%' . $query . '%']);
    foreach ($st->fetchAll() as $pk) {
        $matches[] = honey_build_trace_chain($pdo, $pk);
    }

    return ['matches' => $matches];
}

/** @param array<string, mixed> $pk */
function honey_build_trace_chain(PDO $pdo, array $pk): array
{
    $prodId = isset($pk['production_row_id']) ? (int) $pk['production_row_id'] : 0;
    $pr = $prodId > 0 ? honey_fetch_production($pdo, $prodId) : null;
    $wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;
    $st = $pdo->prepare('SELECT * FROM honey_sales_log WHERE packaging_row_id = ? ORDER BY created_at DESC');
    $st->execute([(int) $pk['id']]);
    $sales = $st->fetchAll();

    return [
        'batch_no' => $pk['batch_no'],
        'packaging' => $pk,
        'production' => $pr,
        'warehouse' => $wh,
        'sales' => $sales,
    ];
}

function honey_correction_row_options(PDO $pdo, string $stage): array
{
    return match ($stage) {
        'warehouse' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['supplier_code'] . ' Â· ' . $r['variant'] . ' Â· ' . $r['current_qty'] . '/' . $r['initial_qty'],
            'row_table' => 'honey_warehouse_rows',
        ], $pdo->query('SELECT id, supplier_code, variant, current_qty, initial_qty FROM honey_warehouse_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        'production' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['supplier_code'] . ' Â· ' . $r['variant'] . ' Â· ' . $r['current_qty'] . '/' . $r['initial_qty'],
            'row_table' => 'honey_production_rows',
        ], $pdo->query('SELECT id, supplier_code, variant, current_qty, initial_qty FROM honey_production_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        'packaging' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['batch_no'] . ' Â· ' . $r['variant'],
            'row_table' => 'honey_packaging_rows',
        ], $pdo->query('SELECT id, batch_no, variant FROM honey_packaging_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        default => [],
    };
}

function honey_executive_adjust(PDO $pdo, string $stage, string $rowTable, int $rowId, float $newCurrent, ?string $size = null): void
{
    honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [
        'current_qty' => $newCurrent,
        'size' => $size,
    ]);
}

/** @param array<string, mixed> $fields */
function honey_executive_qty_resets_baseline(array $fields): bool
{
    return ($fields['qty_mode'] ?? 'current_only') === 'set_exact';
}

/** @param array<string, mixed> $fields */
function honey_executive_edit_row(PDO $pdo, int $userId, string $stage, string $rowTable, int $rowId, array $fields): void
{
    $logSource = isset($fields['log_source']) && is_string($fields['log_source']) && $fields['log_source'] !== ''
        ? (string) $fields['log_source']
        : 'executive_edit';

    $editReason = trim((string) ($fields['reason'] ?? ''));
    $requireReason = ($stage === 'packaging' || $rowTable === 'honey_packaging_rows')
        && $logSource !== 'monthly_inventory';
    if ($requireReason) {
        if (mb_strlen($editReason) < 5) {
            throw new RuntimeException('Enter a reason for this finished honey correction (at least 5 characters).');
        }
        if (mb_strlen($editReason) > 500) {
            throw new RuntimeException('Reason is too long (max 500 characters).');
        }
    }
    $editReason = $editReason !== '' ? mb_substr($editReason, 0, 500) : null;

    if ($rowTable === 'honey_warehouse_rows') {
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            throw new RuntimeException('Warehouse row not found.');
        }
        if (isset($fields['supplier_id'])) {
            $supplierId = (int) $fields['supplier_id'];
            $supplier = supplier_require_honey_active($pdo, $supplierId);
            $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
            $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId, $logSource, $editReason);
            $pdo->prepare(
                'UPDATE honey_warehouse_rows SET supplier_id = ?, supplier_name = ?, supplier_code = ? WHERE id = ?'
            )->execute([$supplierId, $supplierName, $code, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);
        }
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant, $logSource, $editReason);
            $pdo->prepare('UPDATE honey_warehouse_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty, $logSource, $editReason);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty, $logSource, $editReason);
                $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
        }

        return;
    }

    if ($rowTable === 'honey_production_rows') {
        $pr = honey_fetch_production($pdo, $rowId);
        if (!$pr) {
            throw new RuntimeException('Production row not found.');
        }
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant, $logSource, $editReason);
            $pdo->prepare('UPDATE honey_production_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            $pdo->prepare(
                'UPDATE honey_packaging_rows SET variant = ? WHERE production_row_id = ?'
            )->execute([$variant, $rowId]);
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            // Empty lots must not keep a stale cycle max (“0 of 99 gal”).
            if ($qty <= 1e-9) {
                $resetBaseline = true;
            }
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty, $logSource, $editReason);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty, $logSource, $editReason);
                $pdo->prepare('UPDATE honey_production_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE honey_production_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
            honey_production_sync_depleted_at($pdo, $rowId);
            if ($qty > 1e-9) {
                honey_production_clear_depleted_at($pdo, $rowId);
            }
        }
        if (array_key_exists('is_active', $fields)) {
            $active = !empty($fields['is_active']) ? 1 : 0;
            $fresh = honey_fetch_production($pdo, $rowId) ?: $pr;
            $cur = (float) ($fresh['current_qty'] ?? 0);
            if ($active === 0 && $cur > 1e-9) {
                throw new RuntimeException(
                    'Cannot deactivate a lot that still has gallons. Set quantity to 0 first, or leave it active so stock-in can use it.'
                );
            }
            $oldActive = isset($pr['is_active']) ? (string) (int) $pr['is_active'] : '1';
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'is_active', $oldActive, (string) $active, $logSource, $editReason);
            $pdo->prepare('UPDATE honey_production_rows SET is_active = ? WHERE id = ?')->execute([$active, $rowId]);
            // Manual reactivate of an empty lot: restart the 1h grace window.
            if ($active === 1 && honey_production_has_depleted_at($pdo) && $cur <= 1e-9) {
                $pdo->prepare(
                    'UPDATE honey_production_rows SET depleted_at = NOW() WHERE id = ?'
                )->execute([$rowId]);
            }
        }

        return;
    }

    if ($rowTable === 'honey_packaging_rows') {
        $pk = honey_fetch_packaging($pdo, $rowId);
        if (!$pk) {
            throw new RuntimeException('Packaging row not found.');
        }
        $changed = false;
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            $oldVariant = honey_normalize_variant((string) $pk['variant']);
            if ($variant !== $oldVariant) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant, $logSource, $editReason);
                $pdo->prepare('UPDATE honey_packaging_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
                $changed = true;
            }
        }
        $sizeQtys = $fields['size_qtys'] ?? null;
        $resetBaseline = honey_executive_qty_resets_baseline($fields);
        if (is_array($sizeQtys)) {
            foreach ($sizeQtys as $size => $qty) {
                $qty = (float) $qty;
                if ($qty < 0) {
                    throw new RuntimeException('Quantity cannot be negative.');
                }
                [$curCol, $initCol] = honey_packaging_size_columns((string) $size);
                $oldCur = (float) ($pk[$curCol] ?? 0);
                $oldInit = (float) ($pk[$initCol] ?? 0);
                $curChanged = abs($oldCur - $qty) > 0.0001;
                $initChanged = $resetBaseline && abs($oldInit - $qty) > 0.0001;
                if (!$curChanged && !$initChanged) {
                    continue;
                }
                if ($curChanged) {
                    honey_log_edit(
                        $pdo,
                        $userId,
                        $rowTable,
                        $rowId,
                        $curCol,
                        (string) ($pk[$curCol] ?? '0'),
                        (string) $qty,
                        $logSource,
                        $editReason
                    );
                }
                if ($resetBaseline) {
                    if ($initChanged) {
                        honey_log_edit(
                            $pdo,
                            $userId,
                            $rowTable,
                            $rowId,
                            $initCol,
                            (string) ($pk[$initCol] ?? '0'),
                            (string) $qty,
                            $logSource,
                            $editReason
                        );
                    }
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")
                        ->execute([$qty, $qty, $rowId]);
                } else {
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ? WHERE id = ?")->execute([$qty, $rowId]);
                }
                $changed = true;
            }
        } elseif (isset($fields['current_qty'], $fields['size'])) {
            $size = (string) $fields['size'];
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            [$curCol, $initCol] = honey_packaging_size_columns($size);
            $oldCur = (float) ($pk[$curCol] ?? 0);
            $oldInit = (float) ($pk[$initCol] ?? 0);
            $curChanged = abs($oldCur - $qty) > 0.0001;
            $initChanged = $resetBaseline && abs($oldInit - $qty) > 0.0001;
            if ($curChanged || $initChanged) {
                if ($curChanged) {
                    honey_log_edit(
                        $pdo,
                        $userId,
                        $rowTable,
                        $rowId,
                        $curCol,
                        (string) ($pk[$curCol] ?? '0'),
                        (string) $qty,
                        $logSource,
                        $editReason
                    );
                }
                if ($resetBaseline) {
                    if ($initChanged) {
                        honey_log_edit(
                            $pdo,
                            $userId,
                            $rowTable,
                            $rowId,
                            $initCol,
                            (string) ($pk[$initCol] ?? '0'),
                            (string) $qty,
                            $logSource,
                            $editReason
                        );
                    }
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")
                        ->execute([$qty, $qty, $rowId]);
                } else {
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ? WHERE id = ?")->execute([$qty, $rowId]);
                }
                $changed = true;
            }
        }
        if (!$changed) {
            throw new RuntimeException('Nothing changed — update at least one size quantity.');
        }

        return;
    }

    if ($rowTable === 'dept_inventory_rows') {
        $st = $pdo->prepare('SELECT * FROM dept_inventory_rows WHERE id = ? LIMIT 1');
        $st->execute([$rowId]);
        $row = $st->fetch();
        if (!$row) {
            throw new RuntimeException('Dept row not found.');
        }
        if (isset($fields['product_label'])) {
            $label = trim((string) $fields['product_label']);
            if ($label !== '') {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label, $logSource, $editReason);
                $pdo->prepare('UPDATE dept_inventory_rows SET product_label = ? WHERE id = ?')->execute([$label, $rowId]);
            }
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty, $logSource, $editReason);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty, $logSource, $editReason);
                $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
        }

        return;
    }

    throw new RuntimeException('Executive edit not supported for this row.');
}

/** @return array<string, mixed>|null */
function honey_correction_row_context(PDO $pdo, array $req): ?array
{
    $rowTable = (string) ($req['row_table'] ?? '');
    $rowId = (int) ($req['row_id'] ?? 0);
    if ($rowId < 1) {
        return null;
    }

    if ($rowTable === 'honey_warehouse_rows') {
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            return null;
        }

        return [
            'stage_label' => 'Warehouse',
            'supplier_code' => (string) $wh['supplier_code'],
            'supplier_name' => (string) $wh['supplier_name'],
            'arrival_date' => (string) $wh['arrival_date'],
            'variant' => (string) $wh['variant'],
            'current_qty' => (float) $wh['current_qty'],
            'initial_qty' => (float) $wh['initial_qty'],
            'qty_unit' => 'gallon/s',
        ];
    }

    if ($rowTable === 'honey_production_rows') {
        $pr = honey_fetch_production($pdo, $rowId);
        if (!$pr) {
            return null;
        }

        return [
            'stage_label' => 'Production',
            'supplier_code' => (string) $pr['supplier_code'],
            'arrival_date' => (string) $pr['arrival_date'],
            'variant' => (string) $pr['variant'],
            'current_qty' => (float) $pr['current_qty'],
            'initial_qty' => (float) $pr['initial_qty'],
            'qty_unit' => 'gallon/s',
        ];
    }

    if ($rowTable === 'honey_packaging_rows') {
        $pk = honey_fetch_packaging($pdo, $rowId);
        if (!$pk) {
            return null;
        }

        return [
            'stage_label' => 'Packaging',
            'batch_no' => (string) $pk['batch_no'],
            'supplier_code' => (string) $pk['supplier_code'],
            'bottling_date' => (string) $pk['bottling_date'],
            'variant' => (string) $pk['variant'],
            'qty_unit' => 'bottles',
            'sizes' => [
                'liter' => (float) $pk['current_liter'],
                '250ml' => (float) $pk['current_250ml'],
                '350ml' => (float) $pk['current_350ml'],
                'gallon' => (float) $pk['current_gallon'],
            ],
            'initial_sizes' => [
                'liter' => (float) $pk['initial_liter'],
                '250ml' => (float) $pk['initial_250ml'],
                '350ml' => (float) $pk['initial_350ml'],
                'gallon' => (float) $pk['initial_gallon'],
            ],
        ];
    }

    if ($rowTable === 'dept_inventory_rows') {
        $st = $pdo->prepare('SELECT dir.*, d.name AS dept_name FROM dept_inventory_rows dir INNER JOIN departments d ON d.id = dir.dept_id WHERE dir.id = ?');
        $st->execute([$rowId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        return [
            'stage_label' => 'Dept â€” ' . (string) $row['dept_name'],
            'product_label' => (string) $row['product_label'],
            'current_qty' => (float) $row['current_qty'],
            'initial_qty' => (float) $row['initial_qty'],
            'qty_unit' => 'units',
        ];
    }

    return null;
}

/** @param array<string, mixed>|null $ctx */
function honey_correction_change_preview(PDO $pdo, array $req, ?array $ctx): array
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $amount = (float) ($req['amount'] ?? 0);
    $action = (string) ($req['action'] ?? '');
    $newValue = (string) ($req['new_value'] ?? '');
    $size = $req['size'] !== null && $req['size'] !== '' ? (string) $req['size'] : null;

    if (!$ctx) {
        return [
            'headline' => honey_correction_summary($req),
            'detail' => null,
            'before_display' => null,
            'after_display' => null,
        ];
    }

    if ($type === 'variant') {
        $after = honey_normalize_variant($newValue);

        return [
            'headline' => 'Change variant',
            'detail' => ucfirst((string) $ctx['variant']) . ' â†’ ' . ucfirst($after),
            'before_display' => ucfirst((string) $ctx['variant']),
            'after_display' => ucfirst($after),
        ];
    }

    if ($type === 'supplier') {
        $supplierId = (int) $newValue;
        $supplier = supplier_fetch($pdo, $supplierId);
        $newName = $supplier
            ? supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name'])
            : "Supplier #{$supplierId}";
        $newCode = $supplier && isset($ctx['arrival_date'])
            ? honey_supplier_code($newName, (string) $ctx['arrival_date'])
            : 'â€”';

        return [
            'headline' => 'Change supplier',
            'detail' => ($ctx['supplier_name'] ?? $ctx['supplier_code'] ?? '') . ' â†’ ' . $newName,
            'before_display' => (string) ($ctx['supplier_code'] ?? '') . ' Â· ' . ($ctx['supplier_name'] ?? ''),
            'after_display' => $newCode . ' Â· ' . $newName,
        ];
    }

    if ($type === 'packaging_size') {
        $parts = explode(':', $newValue, 2);
        $sz = $parts[0] ?? '';
        $qty = (float) ($parts[1] ?? 0);
        $sizes = $ctx['sizes'] ?? [];
        $initial = $ctx['initial_sizes'] ?? [];
        $before = (float) ($sizes[$sz] ?? 0);
        $init = (float) ($initial[$sz] ?? 0);

        return [
            'headline' => "Set {$sz} bottle count",
            'detail' => "{$before} â†’ {$qty} bottles",
            'before_display' => "{$before} / {$init}",
            'after_display' => "{$qty} / {$qty}",
            'size' => $sz,
        ];
    }

    if ($type === 'set_qty') {
        $qty = (float) $newValue;
        $cur = (float) ($ctx['current_qty'] ?? 0);
        $init = (float) ($ctx['initial_qty'] ?? 0);
        $unit = (string) ($ctx['qty_unit'] ?? '');

        return [
            'headline' => 'Set quantity',
            'detail' => "{$cur} â†’ {$qty} {$unit} (resets baseline)",
            'before_display' => "{$cur} / {$init} {$unit}",
            'after_display' => "{$qty} / {$qty} {$unit}",
        ];
    }

    // qty_delta
    if (isset($ctx['sizes']) && $size) {
        $sizes = $ctx['sizes'];
        $initial = $ctx['initial_sizes'] ?? [];
        $cur = (float) ($sizes[$size] ?? 0);
        $init = (float) ($initial[$size] ?? 0);
        $delta = $action === 'add' ? $amount : -$amount;
        $after = max(0, $cur + $delta);

        return [
            'headline' => ucfirst($action) . " {$amount} {$size} bottles",
            'detail' => "Packaging size: {$size}",
            'before_display' => "{$cur} / {$init}",
            'after_display' => "{$after} / {$init}",
            'size' => $size,
        ];
    }

    $cur = (float) ($ctx['current_qty'] ?? 0);
    $init = (float) ($ctx['initial_qty'] ?? 0);
    $unit = (string) ($ctx['qty_unit'] ?? '');
    $delta = $action === 'add' ? $amount : -$amount;
    $after = max(0, $cur + $delta);

    return [
        'headline' => ucfirst($action) . " {$amount} {$unit}",
        'detail' => null,
        'before_display' => "{$cur} / {$init} {$unit}",
        'after_display' => "{$after} / {$init} {$unit}",
    ];
}

/** @param array<string, mixed> $req */
function honey_correction_enrich(PDO $pdo, array &$req): void
{
    $ctx = honey_correction_row_context($pdo, $req);
    $req['row_context'] = $ctx;
    $req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);
    $req['summary'] = honey_correction_summary($req);
}

/** @param array<string, mixed> $req */
function honey_correction_summary(array $req): string
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $stage = (string) ($req['stage'] ?? '');
    $nv = (string) ($req['new_value'] ?? '');

    return match ($type) {
        'set_qty' => "Set qty to {$nv} ({$stage})",
        'variant' => "Change variant to {$nv}",
        'supplier' => 'Change supplier',
        'packaging_size' => 'Set packaging ' . str_replace(':', ' = ', $nv),
        default => ucfirst((string) ($req['action'] ?? '')) . ' ' . ($req['amount'] ?? '')
            . ($req['size'] ? ' (' . $req['size'] . ')' : '') . " ({$stage})",
    };
}

function honey_correction_unread_count(PDO $pdo): int
{
    try {
        $st = $pdo->query(
            'SELECT COUNT(*) FROM inventory_correction_requests
             WHERE status = "pending" AND executive_viewed_at IS NULL'
        );

        return (int) $st->fetchColumn();
    } catch (Throwable) {
        $st = $pdo->query('SELECT COUNT(*) FROM inventory_correction_requests WHERE status = "pending"');

        return (int) $st->fetchColumn();
    }
}

/** @return list<array<string, mixed>> */
function honey_correction_unread_list(PDO $pdo, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    try {
        $st = $pdo->prepare(
            "SELECT icr.*, u.name AS requested_by_name
             FROM inventory_correction_requests icr
             INNER JOIN users u ON u.id = icr.requested_by
             WHERE icr.status = 'pending' AND icr.executive_viewed_at IS NULL
             ORDER BY icr.created_at DESC
             LIMIT {$limit}"
        );
        $st->execute();
    } catch (Throwable) {
        $st = $pdo->prepare(
            "SELECT icr.*, u.name AS requested_by_name
             FROM inventory_correction_requests icr
             INNER JOIN users u ON u.id = icr.requested_by
             WHERE icr.status = 'pending'
             ORDER BY icr.created_at DESC
             LIMIT {$limit}"
        );
        $st->execute();
    }
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        honey_correction_enrich($pdo, $r);
    }

    return $rows;
}

function honey_correction_mark_viewed(PDO $pdo, int $requestId): void
{
    try {
        $pdo->prepare(
            'UPDATE inventory_correction_requests SET executive_viewed_at = NOW()
             WHERE id = ? AND status = "pending" AND executive_viewed_at IS NULL'
        )->execute([$requestId]);
    } catch (Throwable) {
        // Column may not exist pre-migration.
    }
}

/** Display labels for packaging size breakdown (matches Sales UI). */
const HONEY_PACKAGING_SIZE_LABELS = [
    '250ml' => '250ML',
    '350ml' => '350ML',
    'liter' => '1L',
    'gallon' => 'GALLON',
];

/**
 * Aggregate current on-hand bottles from packaging rows with batch numbers only.
 * Excludes warehouse and production (not bottled / no batch yet).
 *
 * @param list<array<string, mixed>> $rows SQL rows with variant, ml250, ml350, liter, gallon
 * @return array{variants: list<array<string, mixed>>, total: float, batch_count: int}
 */
function honey_packaging_variant_inventory_breakdown(array $rows): array
{
    $sizeKeys = [
        ['key' => '250ml', 'col' => 'ml250'],
        ['key' => '350ml', 'col' => 'ml350'],
        ['key' => 'liter', 'col' => 'liter'],
        ['key' => 'gallon', 'col' => 'gallon'],
    ];

    $variants = [];
    $grandTotal = 0.0;
    $batchCount = 0;

    foreach ($rows as $row) {
        $batchCount += (int) ($row['batch_rows'] ?? 0);
        $variantTotal = 0.0;
        foreach ($sizeKeys as $sk) {
            $variantTotal += (float) ($row[$sk['col']] ?? 0);
        }
        $grandTotal += $variantTotal;
        $sizes = [];
        foreach ($sizeKeys as $sk) {
            $qty = (float) ($row[$sk['col']] ?? 0);
            $sizes[] = [
                'size' => $sk['key'],
                'label' => HONEY_PACKAGING_SIZE_LABELS[$sk['key']] ?? strtoupper($sk['key']),
                'qty' => $qty,
                'pct' => $variantTotal > 0 ? round(($qty / $variantTotal) * 100, 1) : 0.0,
            ];
        }
        $variants[] = [
            'variant' => (string) ($row['variant'] ?? ''),
            'total_bottles' => $variantTotal,
            'sizes' => $sizes,
        ];
    }

    return ['variants' => $variants, 'total' => $grandTotal, 'batch_count' => $batchCount];
}

/** @return array<string, mixed> */
function honey_packaging_total_inventory_snapshot(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT variant,
                COALESCE(SUM(current_250ml), 0) AS ml250,
                COALESCE(SUM(current_350ml), 0) AS ml350,
                COALESCE(SUM(current_liter), 0) AS liter,
                COALESCE(SUM(current_gallon), 0) AS gallon,
                COUNT(*) AS batch_rows
         FROM honey_packaging_rows
         WHERE batch_no IS NOT NULL AND TRIM(batch_no) <> ""
           AND variant IN ("light", "amber", "dark")
         GROUP BY variant
         ORDER BY FIELD(variant, "light", "amber", "dark")'
    );
    $agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);

    $byVariant = [];
    foreach ($agg['variants'] as $v) {
        $byVariant[$v['variant']] = $v;
    }

    $ordered = [];
    foreach (['light', 'amber', 'dark'] as $key) {
        if (isset($byVariant[$key])) {
            $ordered[] = $byVariant[$key];
        }
    }

    return [
        'scope' => 'packaging_batches_only',
        'note' => 'Counts bottled stock with batch numbers. Warehouse and production gallons are excluded.',
        'total_bottles' => $agg['total'],
        'batch_count' => $agg['batch_count'],
        'variants' => $ordered,
    ];
}

/**
 * Executive monthly seed: create production gallons without warehouse transfer deduct.
 *
 * @return array<string, mixed>
 */
function honey_executive_seed_production_row(
    PDO $pdo,
    int $userId,
    int $warehouseRowId,
    float $qtyGallons,
    ?string $variant = null
): array {
    if ($qtyGallons < 0) {
        throw new RuntimeException('Quantity cannot be negative.');
    }
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    $variantVal = $variant !== null && trim($variant) !== ''
        ? honey_normalize_variant($variant)
        : honey_normalize_variant((string) $wh['variant']);

    $ins = $pdo->prepare(
        'INSERT INTO honey_production_rows (warehouse_row_id, supplier_code, arrival_date, variant, current_qty, initial_qty, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $warehouseRowId,
        $wh['supplier_code'],
        $wh['arrival_date'],
        $variantVal,
        $qtyGallons,
        $qtyGallons,
        $userId,
    ]);
    $id = (int) $pdo->lastInsertId();
    honey_log_edit($pdo, $userId, 'honey_production_rows', $id, 'seed_qty', null, (string) $qtyGallons, 'monthly_inventory');

    return [
        'id' => $id,
        'warehouse_row_id' => $warehouseRowId,
        'supplier_code' => (string) $wh['supplier_code'],
        'variant' => $variantVal,
        'current_qty' => $qtyGallons,
        'initial_qty' => $qtyGallons,
    ];
}

/**
 * Executive monthly seed: create packaging batch without production bottling deduct.
 *
 * @param array<string, float|int|string> $sizeQtys
 * @return array<string, mixed>
 */
function honey_executive_seed_packaging_row(
    PDO $pdo,
    int $userId,
    int $productionRowId,
    string $bottlingDate,
    array $sizeQtys
): array {
    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bottlingDate)) {
        throw new RuntimeException('Invalid bottling date.');
    }

    $normalized = [];
    $any = false;
    foreach (HONEY_SIZES as $size) {
        $q = (float) ($sizeQtys[$size] ?? 0);
        if ($q < 0) {
            throw new RuntimeException('Quantity cannot be negative.');
        }
        if ($q > 0) {
            $any = true;
        }
        $normalized[$size] = $q;
    }
    if (!$any) {
        throw new RuntimeException('Enter quantity for at least one size.');
    }

    $batchNo = honey_unique_packaging_batch_no(
        $pdo,
        honey_batch_no((string) $pr['supplier_code'], $bottlingDate),
        (string) $pr['variant'],
        $bottlingDate
    );
    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $productionRowId,
        $pr['supplier_code'],
        $batchNo,
        $bottlingDate,
        $pr['variant'],
        $normalized['liter'],
        $normalized['liter'],
        $normalized['250ml'],
        $normalized['250ml'],
        $normalized['350ml'],
        $normalized['350ml'],
        $normalized['gallon'],
        $normalized['gallon'],
        $userId,
    ]);
    $id = (int) $pdo->lastInsertId();
    foreach (HONEY_SIZES as $size) {
        if ($normalized[$size] <= 0) {
            continue;
        }
        [$curCol] = honey_packaging_size_columns($size);
        honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, 'seed_' . $curCol, null, (string) $normalized[$size], 'monthly_inventory');
    }

    return [
        'id' => $id,
        'batch_no' => $batchNo,
        'production_row_id' => $productionRowId,
        'supplier_code' => (string) $pr['supplier_code'],
        'variant' => (string) $pr['variant'],
        'sizes' => $normalized,
    ];
}



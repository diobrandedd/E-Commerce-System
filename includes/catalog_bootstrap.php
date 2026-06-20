<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_volume.php';

/** Pool-only PT consumables (cost via yield; never production-deduct). */
const CATALOG_PT_YARN_STOCK_NAME = 'Paragis Tea Bag Yarn';
const CATALOG_PT_PAPER_ROLL_STOCK_NAME = 'PT tea bag paper roll';

const CATALOG_JAR_STICKER_STOCK_NAME = 'Jar Cacao sticker';
const CATALOG_JAR_FOIL_STOCK_NAME = 'Jar Cacao foil';

const CATALOG_HONEY_STICKER_STOCK_NAME = 'Honey sticker';
const CATALOG_HONEY_INDUCTION_SEAL_STOCK_NAME = 'Honey induction seal';
const CATALOG_HONEY_CAP_SEAL_STOCK_NAME = 'Honey cap seal';

const CATALOG_POOL_YIELD_PT_TEA_BAG = 10000.0;
const CATALOG_POOL_YIELD_HONEY_BOTTLE = 10000.0;
const CATALOG_POOL_YIELD_JAR_FOIL = 8000.0;

const CATALOG_HONEY_VARIANTS = ['light', 'amber', 'dark'];
const CATALOG_HONEY_SIZES = ['250ml', '350ml', 'liter', 'gallon'];

function catalog_honey_bottle_stock_name(string $size): string
{
    $norm = honey_normalize_bottle_size($size);

    return match ($norm) {
        '250ml' => 'Honey bottle w/ cap - 250ml',
        '350ml' => 'Honey bottle w/ cap - 350ml',
        'liter' => 'Honey bottle w/ cap - 1L',
        'gallon' => 'Honey bottle w/ cap - gallon',
        default => throw new RuntimeException('Invalid honey bottle size.'),
    };
}

function catalog_honey_raw_stock_name(string $variant): string
{
    $v = strtolower(trim($variant));
    if (!in_array($v, CATALOG_HONEY_VARIANTS, true)) {
        throw new RuntimeException('Invalid honey variant.');
    }

    return 'Raw Honey — ' . ucfirst($v);
}

function catalog_honey_formula_liters(string $size): float
{
    return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;
}

/** @param array<string, mixed> $stock */
function catalog_stock_is_pool_packaging(array $stock): bool
{
    if ((string) ($stock['category'] ?? '') !== 'packaging') {
        return false;
    }
    if (!array_key_exists('production_deduct', $stock)) {
        return false;
    }

    return (int) ($stock['production_deduct'] ?? 1) === 0;
}

/**
 * Effective unit price for one consume-unit (tea bag, bottle, jar) on a pool stock row.
 *
 * @param array{current_price?: float|string, pool_yield_qty?: float|string|null} $stock
 */
function catalog_pool_consume_unit_price(array $stock): float
{
    $yield = (float) ($stock['pool_yield_qty'] ?? 0);
    $price = (float) ($stock['current_price'] ?? 0);
    if ($yield <= 0) {
        return 0.0;
    }

    return round($price / $yield, 8);
}

/**
 * Line cost for a formula row linked to a stock.
 *
 * @param array<string, mixed> $stock
 */
function catalog_formula_line_cost(array $stock, float $qtyPerUnit, ?float $frozenUnitPrice = null): float
{
    if ($frozenUnitPrice !== null) {
        $unit = $frozenUnitPrice;
    } elseif (catalog_stock_is_pool_packaging($stock)) {
        $unit = catalog_pool_consume_unit_price($stock);
    } else {
        $unit = (float) ($stock['current_price'] ?? 0);
    }

    return round($qtyPerUnit * $unit, 8);
}

/** Estimated remaining consume-units from pool on_hand. */
function catalog_pool_estimated_remaining(array $stock): ?float
{
    if (!catalog_stock_is_pool_packaging($stock)) {
        return null;
    }
    $yield = (float) ($stock['pool_yield_qty'] ?? 0);
    if ($yield <= 0) {
        return null;
    }

    return round((float) ($stock['on_hand'] ?? 0) * $yield, 2);
}

function catalog_honey_packaging_formula_qty(string $stockName, ?string $productSize): ?float
{
    if ($productSize === null || $productSize === '') {
        return null;
    }
    try {
        $bottleName = catalog_honey_bottle_stock_name($productSize);
    } catch (Throwable) {
        return null;
    }
    if ($stockName === $bottleName) {
        return 1.0;
    }
    if ($stockName === CATALOG_HONEY_STICKER_STOCK_NAME) {
        return 1.0;
    }
    if ($stockName === CATALOG_HONEY_INDUCTION_SEAL_STOCK_NAME || $stockName === CATALOG_HONEY_CAP_SEAL_STOCK_NAME) {
        return 1.0;
    }

    return null;
}

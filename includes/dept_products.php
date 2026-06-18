<?php

declare(strict_types=1);

/**
 * Canonical catalog for single-stage dept products (PT, BRC, Jar Cacao, Honey Comb).
 */

const DEPT_PRODUCT_KEYS = ['pt', 'brc', 'jar_cacao', 'honey_comb'];

/** Supplier raw unit: 1 kg leaf per bag. */
const PT_GRAMS_PER_BAG = 1000;
/** Each individual tea sachet uses 2 g of leaf. */
const PT_GRAMS_PER_TEA_BAG = 2;
/** Sellable pouch packs 24 tea bags (24 × 2 g = 48 g leaf per pouch). */
const PT_TEA_BAGS_PER_POUCH = 24;

const PT_LEAF_STOCK_NAME = 'Paragis leaf — raw';
const PT_TEA_BAG_SACHET_STOCK_NAME = 'PT tea bag sachet';
const PT_PACKAGING_STOCK_NAME = 'PT pouch packaging';

/** Supplier raw unit: 1 kg per bag. */
const BRC_GRAMS_PER_BAG = 1000;
/** Sellable pouch contains ~100 g product. */
const BRC_GRAMS_PER_POUCH = 100;

const BRC_RAW_STOCK_NAME = 'Black Rice Coffee — raw';
const BRC_PACKAGING_STOCK_NAME = 'BRC pouch packaging';

/** Supplier raw unit: 1 kg per bag; sellable jar = 220 g cacao (24 tablets inside). */
const JAR_CACAO_GRAMS_PER_BAG = 1000;
const JAR_CACAO_GRAMS_PER_JAR = 220;
const JAR_CACAO_TABLETS_PER_JAR = 24;

const JAR_CACAO_RAW_STOCK_NAME = 'Jar Cacao — raw';
const JAR_CACAO_PACKAGING_STOCK_NAME = 'Jar Cacao jar packaging';
const JAR_CACAO_STICKER_STOCK_NAME = 'Jar Cacao sticker';
const JAR_CACAO_FOIL_STOCK_NAME = 'Jar Cacao foil';

/** Supplier raw unit: 1 kg per bag; sellable jar = 500 g comb (default yield — adjust in spec if ops differs). */
const HONEY_COMB_GRAMS_PER_BAG = 1000;
const HONEY_COMB_GRAMS_PER_JAR = 500;

const HONEY_COMB_RAW_STOCK_NAME = 'Honey comb — raw';
const HONEY_COMB_JAR_STOCK_NAME = 'Honey comb jar packaging';
const HONEY_COMB_STICKER_STOCK_NAME = 'Honey comb sticker';

const PT_YARN_STOCK_NAME = 'Paragis Tea Bag Yarn';
const PT_PAPER_ROLL_STOCK_NAME = 'PT tea bag paper roll';

function dept_pt_tea_bags_per_bag(): float
{
    return PT_GRAMS_PER_BAG / PT_GRAMS_PER_TEA_BAG;
}

function dept_pt_pouches_per_bag(): float
{
    return dept_pt_tea_bags_per_bag() / PT_TEA_BAGS_PER_POUCH;
}

function dept_pt_bags_per_pouch(): float
{
    return PT_TEA_BAGS_PER_POUCH / dept_pt_tea_bags_per_bag();
}

function dept_brc_pouches_per_bag(): float
{
    return BRC_GRAMS_PER_BAG / BRC_GRAMS_PER_POUCH;
}

function dept_brc_bags_per_pouch(): float
{
    return BRC_GRAMS_PER_POUCH / BRC_GRAMS_PER_BAG;
}

function dept_jar_cacao_jars_per_bag(): float
{
    return JAR_CACAO_GRAMS_PER_BAG / JAR_CACAO_GRAMS_PER_JAR;
}

function dept_jar_cacao_bags_per_jar(): float
{
    return JAR_CACAO_GRAMS_PER_JAR / JAR_CACAO_GRAMS_PER_BAG;
}

function dept_honey_comb_jars_per_bag(): float
{
    return HONEY_COMB_GRAMS_PER_BAG / HONEY_COMB_GRAMS_PER_JAR;
}

function dept_honey_comb_bags_per_jar(): float
{
    return HONEY_COMB_GRAMS_PER_JAR / HONEY_COMB_GRAMS_PER_BAG;
}

function dept_product_uses_supplier_pricing(string $key): bool
{
    $def = dept_product_by_key($key);

    return $def !== null && ($def['uses_supplier_pricing'] ?? false);
}

function dept_product_sell_units_per_bag(string $key): float
{
    return match (strtolower(trim($key))) {
        'pt' => dept_pt_pouches_per_bag(),
        'brc' => dept_brc_pouches_per_bag(),
        'jar_cacao' => dept_jar_cacao_jars_per_bag(),
        'honey_comb' => dept_honey_comb_jars_per_bag(),
        default => throw new RuntimeException('No bag→sell-unit conversion for product: ' . $key),
    };
}

/** @deprecated Use dept_product_sell_units_per_bag() */
function dept_product_pouches_per_bag(string $key): float
{
    return dept_product_sell_units_per_bag($key);
}

function dept_product_bags_per_sell_unit(string $key): float
{
    return match (strtolower(trim($key))) {
        'pt' => dept_pt_bags_per_pouch(),
        'brc' => dept_brc_bags_per_pouch(),
        'jar_cacao' => dept_jar_cacao_bags_per_jar(),
        'honey_comb' => dept_honey_comb_bags_per_jar(),
        default => throw new RuntimeException('No sell-unit BOM fraction for product: ' . $key),
    };
}

/** @deprecated Use dept_product_bags_per_sell_unit() */
function dept_product_bags_per_pouch(string $key): float
{
    return dept_product_bags_per_sell_unit($key);
}

/** @return list<array{key: string, label: string, code: string, dept: string, unit: string, stock_in_unit: string, inventory_unit: string, uses_supplier_pricing: bool, stock_name: ?string, leaf_stock_name: ?string, packaging_stock_name: ?string, patterns: list<string>}> */
function dept_product_catalog(): array
{
    return [
        [
            'key' => 'pt',
            'label' => 'Paragis Tea',
            'code' => 'PT',
            'dept' => 'paragis',
            'unit' => 'pouch',
            'stock_in_unit' => 'bag',
            'inventory_unit' => 'pouch',
            'uses_supplier_pricing' => true,
            'stock_name' => null,
            'leaf_stock_name' => PT_LEAF_STOCK_NAME,
            'packaging_stock_name' => PT_PACKAGING_STOCK_NAME,
            'tea_bag_sachet_stock_name' => PT_TEA_BAG_SACHET_STOCK_NAME,
            'patterns' => ['paragis_tea', 'paragis tea', 'paragis tea pouch', 'pouch_pt', 'paragis', '\bpt\b'],
        ],
        [
            'key' => 'brc',
            'label' => 'Black Rice Coffee',
            'code' => 'BRC',
            'dept' => 'cacao_brc',
            'unit' => 'pouch',
            'stock_in_unit' => 'bag',
            'inventory_unit' => 'pouch',
            'uses_supplier_pricing' => true,
            'stock_name' => null,
            'leaf_stock_name' => BRC_RAW_STOCK_NAME,
            'packaging_stock_name' => BRC_PACKAGING_STOCK_NAME,
            'patterns' => [
                'black_rice_tea',
                'black rice tea',
                'black_rice_coffee',
                'black rice coffee',
                'pouch_brc',
                '\bbrc\b',
            ],
        ],
        [
            'key' => 'jar_cacao',
            'label' => 'Jar Cacao',
            'code' => '',
            'dept' => 'cacao_brc',
            'unit' => 'pcs',
            'stock_in_unit' => 'bag',
            'inventory_unit' => 'pcs',
            'uses_supplier_pricing' => true,
            'stock_name' => null,
            'leaf_stock_name' => JAR_CACAO_RAW_STOCK_NAME,
            'packaging_stock_name' => JAR_CACAO_PACKAGING_STOCK_NAME,
            'patterns' => ['jar_cacao', 'jar cacao', 'jar_cacao_with_honey', 'jar cacao with honey', 'cacao', 'tablea'],
        ],
        [
            'key' => 'honey_comb',
            'label' => 'Honey Comb',
            'code' => '',
            'dept' => 'honey',
            'unit' => 'pcs',
            'stock_in_unit' => 'bag',
            'inventory_unit' => 'pcs',
            'uses_supplier_pricing' => true,
            'stock_name' => null,
            'leaf_stock_name' => HONEY_COMB_RAW_STOCK_NAME,
            'packaging_stock_name' => HONEY_COMB_JAR_STOCK_NAME,
            'patterns' => ['honey_comb', 'jar_honey_comb', 'honey comb', 'honeycomb'],
        ],
    ];
}

/** @return array<string, mixed>|null */
function dept_product_by_key(string $key): ?array
{
    $key = strtolower(trim($key));
    foreach (dept_product_catalog() as $def) {
        if ($def['key'] === $key) {
            return $def;
        }
    }

    return null;
}

function dept_product_key_valid(string $key): bool
{
    return dept_product_by_key($key) !== null;
}

/** @return array<string, mixed>|null */
function dept_product_resolve_label(string $label): ?array
{
    $norm = strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? $label));
    if ($norm === '') {
        return null;
    }

    foreach (dept_product_catalog() as $def) {
        foreach ($def['patterns'] as $pattern) {
            $p = strtolower(trim($pattern));
            if ($p === '') {
                continue;
            }
            if (str_starts_with($p, '\\b')) {
                if (preg_match('/' . $p . '/i', $norm)) {
                    return $def;
                }
                continue;
            }
            if ($norm === $p || str_contains($norm, $p)) {
                return $def;
            }
        }
    }

    return null;
}

/** @return array<string, mixed>|null */
function dept_product_resolve_segment(string $segment): ?array
{
    if (preg_match('/honey\s*comb|honeycomb/i', $segment)) {
        return dept_product_by_key('honey_comb');
    }
    if (preg_match('/honey/i', $segment)) {
        return null;
    }

    if (preg_match('/paragis/i', $segment)) {
        return dept_product_by_key('pt');
    }
    if (preg_match('/black\s*rice\s*coffee|\bbrc\b/i', $segment)) {
        return dept_product_by_key('brc');
    }
    if (preg_match('/\bcacao|tablea|jar\s*cacao/i', $segment)) {
        return dept_product_by_key('jar_cacao');
    }

    return dept_product_resolve_label($segment);
}

/** @return list<array<string, mixed>> */
function dept_products_for_dept(string $deptKey): array
{
    $deptKey = strtolower(trim($deptKey));
    $out = [];
    foreach (dept_product_catalog() as $def) {
        if ($def['dept'] === $deptKey) {
            $out[] = $def;
        }
    }

    return $out;
}

function dept_product_dept_id(PDO $pdo, string $deptKey): int
{
    $st = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
    $st->execute([$deptKey]);
    $id = (int) $st->fetchColumn();
    if ($id < 1) {
        throw new RuntimeException('Department not found: ' . $deptKey);
    }

    return $id;
}

function dept_pricing_supplier_category_for_product(string $productKey): ?string
{
    return match (strtolower(trim($productKey))) {
        'pt' => 'paragis',
        'brc' => 'black_rice',
        'jar_cacao' => 'cacao',
        'honey_comb' => 'honey_comb',
        default => null,
    };
}

/** BOM qty per finished unit when stock matches this product's formula line. */
function dept_product_formula_qty_for_stock(string $productKey, string $stockName): ?float
{
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        return null;
    }
    $stockName = trim($stockName);
    $leaf = $def['leaf_stock_name'] ?? null;
    if ($leaf !== null && $stockName === $leaf) {
        return dept_product_bags_per_sell_unit((string) $def['key']);
    }
    $pack = $def['packaging_stock_name'] ?? null;
    if ($pack !== null && $stockName === $pack) {
        return 1.0;
    }
    if ($productKey === 'jar_cacao' && $stockName === JAR_CACAO_STICKER_STOCK_NAME) {
        return 1.0;
    }
    if ($productKey === 'pt' && in_array($stockName, [PT_YARN_STOCK_NAME, PT_PAPER_ROLL_STOCK_NAME], true)) {
        return (float) PT_TEA_BAGS_PER_POUCH;
    }
    if ($productKey === 'jar_cacao' && $stockName === JAR_CACAO_FOIL_STOCK_NAME) {
        return 1.0;
    }
    if ($productKey === 'honey_comb' && $stockName === HONEY_COMB_STICKER_STOCK_NAME) {
        return 1.0;
    }
    $legacy = $def['stock_name'] ?? null;
    if ($legacy !== null && $stockName === $legacy) {
        return 1.0;
    }

    return null;
}

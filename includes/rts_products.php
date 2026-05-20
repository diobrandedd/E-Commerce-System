<?php

declare(strict_types=1);

/**
 * Single source of truth for RTS product types, sizes, variants, and combo products.
 */

const RTS_PRODUCT_TYPES = [
    'honey',
    'honey_with_other',
    'pouch_pt',
    'pouch_pt_with_honey',
    'pouch_brc',
    'jar_cacao',
    'jar_cacao_with_honey',
    'jar_honey_comb',
];

const RTS_PRODUCT_LABELS = [
    'honey' => 'Honey',
    'honey_with_other' => 'Honey w/ other',
    'pouch_pt' => 'Pouch PT',
    'pouch_pt_with_honey' => 'Pouch PT w/ Honey',
    'pouch_brc' => 'Pouch BRC',
    'jar_cacao' => 'Jar Cacao',
    'jar_cacao_with_honey' => 'Jar Cacao w/ Honey',
    'jar_honey_comb' => 'Jar Honey Comb',
];

const RTS_HONEY_SIZES = ['250ml', '350ml', 'liter', 'gallon'];

const RTS_HONEY_SIZE_LABELS = [
    '250ml' => '250ml',
    '350ml' => '350ml',
    'liter' => 'Liter',
    'gallon' => 'Gallon',
];

const RTS_HONEY_VARIANTS = ['light', 'amber', 'dark'];

const RTS_HONEY_VARIANT_LABELS = [
    'light' => 'Light',
    'amber' => 'Amber',
    'dark' => 'Dark',
];

/** Products available in the "Honey w/ other" combo dropdown. */
const RTS_COMBO_PRODUCTS = ['paragis_tea', 'black_rice_coffee', 'jar_cacao', 'jar_honey_comb'];

const RTS_COMBO_PRODUCT_LABELS = [
    'paragis_tea' => 'Paragis Tea',
    'black_rice_coffee' => 'Black Rice Coffee',
    'jar_cacao' => 'Jar Cacao',
    'jar_honey_comb' => 'Jar Honey Comb',
];

/** Product types where the main item requires honey size + variant. */
const RTS_TYPES_WITH_HONEY_MAIN = ['honey', 'honey_with_other'];

/** Product types that have a combo item (paired product). */
const RTS_TYPES_WITH_COMBO = ['honey_with_other', 'pouch_pt_with_honey', 'jar_cacao_with_honey'];

/** Product types where the combo item IS honey (needs combo_honey_size + combo_honey_variant). */
const RTS_TYPES_WITH_COMBO_HONEY = ['pouch_pt_with_honey', 'jar_cacao_with_honey'];

/** Product types where the combo item is a non-honey product slug (needs combo_product + combo_quantity). */
const RTS_TYPES_WITH_COMBO_OTHER = ['honey_with_other'];

function rts_product_type_valid(string $type): bool
{
    return in_array($type, RTS_PRODUCT_TYPES, true);
}

function rts_product_label(string $type): string
{
    return RTS_PRODUCT_LABELS[$type] ?? ucwords(str_replace('_', ' ', $type));
}

function rts_honey_size_valid(string $size): bool
{
    return in_array($size, RTS_HONEY_SIZES, true);
}

function rts_honey_variant_valid(string $variant): bool
{
    return in_array($variant, RTS_HONEY_VARIANTS, true);
}

function rts_combo_product_valid(string $product): bool
{
    return in_array($product, RTS_COMBO_PRODUCTS, true);
}

/** Whether this product type needs honey_size and honey_variant on the main item. */
function rts_type_needs_honey(string $type): bool
{
    return in_array($type, RTS_TYPES_WITH_HONEY_MAIN, true);
}

/** Whether this product type has a combo section. */
function rts_type_has_combo(string $type): bool
{
    return in_array($type, RTS_TYPES_WITH_COMBO, true);
}

/** Whether the combo item for this type is honey (needs size+variant). */
function rts_type_combo_is_honey(string $type): bool
{
    return in_array($type, RTS_TYPES_WITH_COMBO_HONEY, true);
}

/** Whether the combo item for this type is a non-honey product (needs combo_product slug). */
function rts_type_combo_is_other(string $type): bool
{
    return in_array($type, RTS_TYPES_WITH_COMBO_OTHER, true);
}

/**
 * Validate a single line item array. Returns error string or null if valid.
 */
function rts_validate_line(array $line, int $index = 0): ?string
{
    $pt = trim((string) ($line['product_type'] ?? ''));
    if (!rts_product_type_valid($pt)) {
        return "Line #{$index}: invalid product_type";
    }

    $qty = isset($line['quantity']) ? (float) $line['quantity'] : 0;
    if ($qty < 0.01) {
        return "Line #{$index}: quantity must be at least 0.01";
    }

    // Honey main fields
    if (rts_type_needs_honey($pt)) {
        $hs = trim((string) ($line['honey_size'] ?? ''));
        $hv = trim((string) ($line['honey_variant'] ?? ''));
        if (!rts_honey_size_valid($hs)) {
            return "Line #{$index}: invalid honey_size";
        }
        if (!rts_honey_variant_valid($hv)) {
            return "Line #{$index}: invalid honey_variant";
        }
    }

    // Combo: other product (only required when combo_product is set — continuation variant rows omit it)
    if (rts_type_combo_is_other($pt)) {
        $cp = trim((string) ($line['combo_product'] ?? ''));
        if ($cp !== '') {
            if (!rts_combo_product_valid($cp)) {
                return "Line #{$index}: invalid combo_product";
            }
            $cq = isset($line['combo_quantity']) ? (float) $line['combo_quantity'] : 0;
            if ($cq < 0.01) {
                return "Line #{$index}: combo_quantity must be at least 0.01";
            }
        }
    }

    // Combo: honey (variant/qty optional on continuation rows)
    if (rts_type_combo_is_honey($pt)) {
        $chs = trim((string) ($line['combo_honey_size'] ?? ''));
        $chv = trim((string) ($line['combo_honey_variant'] ?? ''));
        if ($chs !== '' || $chv !== '') {
            if (!rts_honey_size_valid($chs)) {
                return "Line #{$index}: invalid combo_honey_size";
            }
            if (!rts_honey_variant_valid($chv)) {
                return "Line #{$index}: invalid combo_honey_variant";
            }
            $cq = isset($line['combo_quantity']) ? (float) $line['combo_quantity'] : 0;
            if ($cq < 0.01) {
                return "Line #{$index}: combo_quantity must be at least 0.01";
            }
        }
    }

    return null;
}

/**
 * Validate all lines. Returns first error or null.
 */
function rts_validate_lines_v2(array $lines): ?string
{
    if ($lines === []) {
        return 'At least one line item is required';
    }
    foreach ($lines as $i => $line) {
        $err = rts_validate_line($line, $i + 1);
        if ($err !== null) {
            return $err;
        }
    }

    return null;
}

/**
 * Normalize a line item for DB insertion. Returns cleaned array.
 */
function rts_normalize_line(array $line): array
{
    $pt = trim((string) ($line['product_type'] ?? ''));

    $out = [
        'product_type' => $pt,
        'honey_size' => null,
        'honey_variant' => null,
        'combo_product' => null,
        'combo_quantity' => null,
        'combo_honey_size' => null,
        'combo_honey_variant' => null,
        'quantity' => round((float) ($line['quantity'] ?? 0), 2),
        'is_bonus' => !empty($line['is_bonus']) ? 1 : 0,
    ];

    if (rts_type_needs_honey($pt)) {
        $out['honey_size'] = trim((string) ($line['honey_size'] ?? ''));
        $out['honey_variant'] = trim((string) ($line['honey_variant'] ?? ''));
    }

    if (rts_type_combo_is_other($pt)) {
        $out['combo_product'] = trim((string) ($line['combo_product'] ?? ''));
        $out['combo_quantity'] = round((float) ($line['combo_quantity'] ?? 0), 2);
    }

    if (rts_type_combo_is_honey($pt)) {
        $out['combo_honey_size'] = trim((string) ($line['combo_honey_size'] ?? ''));
        $out['combo_honey_variant'] = trim((string) ($line['combo_honey_variant'] ?? ''));
        $out['combo_quantity'] = round((float) ($line['combo_quantity'] ?? 0), 2);
    }

    return $out;
}

/**
 * Format a line item for human-readable display.
 */
function rts_line_display(array $row): string
{
    $pt = (string) ($row['product_type'] ?? '');
    $qty = (float) ($row['quantity'] ?? 0);
    $label = RTS_PRODUCT_LABELS[$pt] ?? $pt;
    $parts = [];

    if (rts_type_needs_honey($pt)) {
        $sz = RTS_HONEY_SIZE_LABELS[$row['honey_size'] ?? ''] ?? ($row['honey_size'] ?? '');
        $var = RTS_HONEY_VARIANT_LABELS[$row['honey_variant'] ?? ''] ?? ($row['honey_variant'] ?? '');
        $parts[] = $qty . ' x Honey ' . $var . ' (' . $sz . ')';
    } else {
        $parts[] = $qty . ' x ' . $label;
    }

    // Combo other
    if (rts_type_combo_is_other($pt) && !empty($row['combo_product'])) {
        $cp = RTS_COMBO_PRODUCT_LABELS[$row['combo_product']] ?? $row['combo_product'];
        $cq = (float) ($row['combo_quantity'] ?? 0);
        $parts[] = '+ ' . $cq . ' x ' . $cp;
    }

    // Combo honey
    if (rts_type_combo_is_honey($pt) && !empty($row['combo_honey_size'])) {
        $csz = RTS_HONEY_SIZE_LABELS[$row['combo_honey_size'] ?? ''] ?? ($row['combo_honey_size'] ?? '');
        $cvar = RTS_HONEY_VARIANT_LABELS[$row['combo_honey_variant'] ?? ''] ?? ($row['combo_honey_variant'] ?? '');
        $cq = (float) ($row['combo_quantity'] ?? 0);
        $parts[] = '+ ' . $cq . ' x Honey ' . $cvar . ' (' . $csz . ')';
    }

    $text = implode(' ', $parts);
    if (!empty($row['is_bonus'])) {
        $text = '[Bonus] ' . $text;
    }

    return $text;
}

/** True when rts_line_items has v2 columns (product_type, honey_variant, …). */
function rts_line_items_schema_v2(PDO $pdo, bool $fresh = false): bool
{
    static $v2 = null;
    if (!$fresh && $v2 !== null) {
        return $v2;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `rts_line_items` LIKE 'product_type'");
        $v2 = $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable) {
        $v2 = false;
    }

    return $v2;
}


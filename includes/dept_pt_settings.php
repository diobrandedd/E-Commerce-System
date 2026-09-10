<?php

declare(strict_types=1);

/**
 * Paragis Tea BOM settings (grams per tea bag × tea bags per pouch).
 * Defaults match PT_* constants in dept_products.php.
 */

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/dept_products.php';

const PT_GRAMS_PER_TEA_BAG_SETTING_KEY = 'pt.grams_per_tea_bag';
const PT_TEA_BAGS_PER_POUCH_SETTING_KEY = 'pt.tea_bags_per_pouch';
const PT_BAG_POOL_DEDUCT_SETTING_KEY = 'pt.bag_pool_deduct';
const PT_GRAMS_PER_TEA_BAG_MIN = 0.1;
const PT_GRAMS_PER_TEA_BAG_MAX = 50.0;
const PT_TEA_BAGS_PER_POUCH_MIN = 1.0;
const PT_TEA_BAGS_PER_POUCH_MAX = 500.0;

function dept_pt_setting_parse_bool(?string $raw, bool $default = true): bool
{
    if ($raw === null || $raw === '') {
        return $default;
    }
    $v = strtolower(trim($raw));
    if (in_array($v, ['0', 'false', 'off', 'no'], true)) {
        return false;
    }
    if (in_array($v, ['1', 'true', 'on', 'yes'], true)) {
        return true;
    }

    return $default;
}

/** When true (default), finished pouch stock-in deducts the raw bag pool. */
function dept_pt_setting_bag_pool_deduct_enabled(?PDO $pdo = null): bool
{
    if ($pdo === null) {
        return true;
    }

    return dept_pt_setting_parse_bool(app_setting_get($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, null), true);
}

function dept_pt_setting_grams_per_tea_bag(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) PT_GRAMS_PER_TEA_BAG;
    }
    $raw = app_setting_get($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) PT_GRAMS_PER_TEA_BAG;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < PT_GRAMS_PER_TEA_BAG_MIN || $n > PT_GRAMS_PER_TEA_BAG_MAX) {
        return (float) PT_GRAMS_PER_TEA_BAG;
    }

    return $n;
}

function dept_pt_setting_tea_bags_per_pouch(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) PT_TEA_BAGS_PER_POUCH;
    }
    $raw = app_setting_get($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) PT_TEA_BAGS_PER_POUCH;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < PT_TEA_BAGS_PER_POUCH_MIN || $n > PT_TEA_BAGS_PER_POUCH_MAX) {
        return (float) PT_TEA_BAGS_PER_POUCH;
    }

    return $n;
}

function dept_pt_setting_grams_per_pouch(?PDO $pdo = null): float
{
    return round(
        dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),
        6
    );
}

/** Fraction of a 1 kg supplier bag consumed by one pouch. */
function dept_pt_setting_bags_per_pouch(?PDO $pdo = null): float
{
    $g = dept_pt_setting_grams_per_pouch($pdo);
    if ($g <= 0) {
        return dept_pt_bags_per_pouch();
    }

    return round($g / (float) PT_GRAMS_PER_BAG, 8);
}

/**
 * @return array{
 *   grams_per_tea_bag: float,
 *   tea_bags_per_pouch: float,
 *   grams_per_pouch: float,
 *   grams_per_bag: float,
 *   pouches_per_bag: float,
 *   bag_pool_deduct: bool,
 *   default_grams_per_tea_bag: float,
 *   default_tea_bags_per_pouch: float
 * }
 */
function dept_pt_bom_snapshot(?PDO $pdo = null): array
{
    $gTea = dept_pt_setting_grams_per_tea_bag($pdo);
    $perPouch = dept_pt_setting_tea_bags_per_pouch($pdo);
    $gPouch = round($gTea * $perPouch, 6);

    return [
        'grams_per_tea_bag' => $gTea,
        'tea_bags_per_pouch' => $perPouch,
        'grams_per_pouch' => $gPouch,
        'grams_per_bag' => (float) PT_GRAMS_PER_BAG,
        'pouches_per_bag' => $gPouch > 0 ? round((float) PT_GRAMS_PER_BAG / $gPouch, 4) : 0.0,
        'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),
        'default_grams_per_tea_bag' => (float) PT_GRAMS_PER_TEA_BAG,
        'default_tea_bags_per_pouch' => (float) PT_TEA_BAGS_PER_POUCH,
    ];
}

/**
 * @return array{grams_per_tea_bag: float, tea_bags_per_pouch: float, grams_per_pouch: float, bag_pool_deduct: bool}
 */
function dept_pt_bom_save(
    PDO $pdo,
    float $gramsPerTeaBag,
    float $teaBagsPerPouch,
    bool $bagPoolDeduct = true,
    ?int $userId = null
): array {
    if (!is_finite($gramsPerTeaBag) || $gramsPerTeaBag < PT_GRAMS_PER_TEA_BAG_MIN || $gramsPerTeaBag > PT_GRAMS_PER_TEA_BAG_MAX) {
        throw new RuntimeException(
            'Grams per tea bag must be between ' . PT_GRAMS_PER_TEA_BAG_MIN . ' and ' . PT_GRAMS_PER_TEA_BAG_MAX . '.'
        );
    }
    if (!is_finite($teaBagsPerPouch) || $teaBagsPerPouch < PT_TEA_BAGS_PER_POUCH_MIN || $teaBagsPerPouch > PT_TEA_BAGS_PER_POUCH_MAX) {
        throw new RuntimeException(
            'Tea bags per pouch must be between ' . PT_TEA_BAGS_PER_POUCH_MIN . ' and ' . PT_TEA_BAGS_PER_POUCH_MAX . '.'
        );
    }
    app_setting_set($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, (string) $gramsPerTeaBag, $userId);
    app_setting_set($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, (string) $teaBagsPerPouch, $userId);
    app_setting_set($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);

    return [
        'grams_per_tea_bag' => dept_pt_setting_grams_per_tea_bag($pdo),
        'tea_bags_per_pouch' => dept_pt_setting_tea_bags_per_pouch($pdo),
        'grams_per_pouch' => dept_pt_setting_grams_per_pouch($pdo),
        'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),
    ];
}

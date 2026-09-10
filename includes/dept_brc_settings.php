<?php

declare(strict_types=1);

/**
 * Black Rice Coffee BOM settings (grams per pouch + grams per bag).
 * Defaults match BRC_* constants in dept_products.php.
 */

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/dept_products.php';

const BRC_GRAMS_PER_POUCH_SETTING_KEY = 'brc.grams_per_pouch';
const BRC_GRAMS_PER_BAG_SETTING_KEY = 'brc.grams_per_bag';
const BRC_BAG_POOL_DEDUCT_SETTING_KEY = 'brc.bag_pool_deduct';
const BRC_GRAMS_PER_POUCH_MIN = 1.0;
const BRC_GRAMS_PER_POUCH_MAX = 2000.0;
const BRC_GRAMS_PER_BAG_MIN = 100.0;
const BRC_GRAMS_PER_BAG_MAX = 50000.0;

function dept_brc_setting_parse_bool(?string $raw, bool $default = true): bool
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
function dept_brc_setting_bag_pool_deduct_enabled(?PDO $pdo = null): bool
{
    if ($pdo === null) {
        return true;
    }

    return dept_brc_setting_parse_bool(app_setting_get($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, null), true);
}

function dept_brc_setting_grams_per_pouch(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) BRC_GRAMS_PER_POUCH;
    }
    $raw = app_setting_get($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) BRC_GRAMS_PER_POUCH;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < BRC_GRAMS_PER_POUCH_MIN || $n > BRC_GRAMS_PER_POUCH_MAX) {
        return (float) BRC_GRAMS_PER_POUCH;
    }

    return $n;
}

function dept_brc_setting_grams_per_bag(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) BRC_GRAMS_PER_BAG;
    }
    $raw = app_setting_get($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) BRC_GRAMS_PER_BAG;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < BRC_GRAMS_PER_BAG_MIN || $n > BRC_GRAMS_PER_BAG_MAX) {
        return (float) BRC_GRAMS_PER_BAG;
    }

    return $n;
}

/** Fraction of one supplier bag consumed by one pouch. */
function dept_brc_setting_bags_per_pouch(?PDO $pdo = null): float
{
    $gPouch = dept_brc_setting_grams_per_pouch($pdo);
    $gBag = dept_brc_setting_grams_per_bag($pdo);
    if ($gPouch <= 0 || $gBag <= 0) {
        return dept_brc_bags_per_pouch();
    }

    return round($gPouch / $gBag, 8);
}

/**
 * @return array{
 *   grams_per_pouch: float,
 *   grams_per_bag: float,
 *   pouches_per_bag: float,
 *   bags_per_pouch: float,
 *   bag_pool_deduct: bool,
 *   default_grams_per_pouch: float,
 *   default_grams_per_bag: float
 * }
 */
function dept_brc_bom_snapshot(?PDO $pdo = null): array
{
    $gPouch = dept_brc_setting_grams_per_pouch($pdo);
    $gBag = dept_brc_setting_grams_per_bag($pdo);

    return [
        'grams_per_pouch' => $gPouch,
        'grams_per_bag' => $gBag,
        'pouches_per_bag' => $gPouch > 0 ? round($gBag / $gPouch, 4) : 0.0,
        'bags_per_pouch' => dept_brc_setting_bags_per_pouch($pdo),
        'bag_pool_deduct' => dept_brc_setting_bag_pool_deduct_enabled($pdo),
        'default_grams_per_pouch' => (float) BRC_GRAMS_PER_POUCH,
        'default_grams_per_bag' => (float) BRC_GRAMS_PER_BAG,
    ];
}

/**
 * @return array{grams_per_pouch: float, grams_per_bag: float, pouches_per_bag: float, bag_pool_deduct: bool}
 */
function dept_brc_bom_save(
    PDO $pdo,
    float $gramsPerPouch,
    float $gramsPerBag,
    bool $bagPoolDeduct = true,
    ?int $userId = null
): array {
    if (!is_finite($gramsPerPouch) || $gramsPerPouch < BRC_GRAMS_PER_POUCH_MIN || $gramsPerPouch > BRC_GRAMS_PER_POUCH_MAX) {
        throw new RuntimeException(
            'Grams per pouch must be between ' . BRC_GRAMS_PER_POUCH_MIN . ' and ' . BRC_GRAMS_PER_POUCH_MAX . '.'
        );
    }
    if (!is_finite($gramsPerBag) || $gramsPerBag < BRC_GRAMS_PER_BAG_MIN || $gramsPerBag > BRC_GRAMS_PER_BAG_MAX) {
        throw new RuntimeException(
            'Grams per bag must be between ' . BRC_GRAMS_PER_BAG_MIN . ' and ' . BRC_GRAMS_PER_BAG_MAX . '.'
        );
    }
    if ($gramsPerPouch > $gramsPerBag) {
        throw new RuntimeException('Grams per pouch cannot exceed grams per bag.');
    }
    app_setting_set($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, (string) $gramsPerPouch, $userId);
    app_setting_set($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);
    app_setting_set($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);

    $snap = dept_brc_bom_snapshot($pdo);

    return [
        'grams_per_pouch' => $snap['grams_per_pouch'],
        'grams_per_bag' => $snap['grams_per_bag'],
        'pouches_per_bag' => $snap['pouches_per_bag'],
        'bag_pool_deduct' => $snap['bag_pool_deduct'],
    ];
}

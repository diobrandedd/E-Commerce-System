<?php

declare(strict_types=1);

/**
 * Jar Cacao BOM settings (grams per jar + grams per bag).
 * Defaults match JAR_CACAO_* constants in dept_products.php.
 */

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/dept_products.php';

const JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY = 'jar_cacao.grams_per_jar';
const JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY = 'jar_cacao.grams_per_bag';
const JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY = 'jar_cacao.bag_pool_deduct';
const JAR_CACAO_FREEBIE_GRAMS_SETTING_KEY = 'jar_cacao.freebie_grams';
const JAR_CACAO_FREEBIE_GRAMS_DEFAULT = 5.0;
const JAR_CACAO_FREEBIE_GRAMS_MIN = 0.1;
const JAR_CACAO_FREEBIE_GRAMS_MAX = 500.0;
const JAR_CACAO_GRAMS_PER_JAR_MIN = 1.0;
const JAR_CACAO_GRAMS_PER_JAR_MAX = 2000.0;
const JAR_CACAO_GRAMS_PER_BAG_MIN = 100.0;
const JAR_CACAO_GRAMS_PER_BAG_MAX = 50000.0;

function dept_jar_cacao_setting_parse_bool(?string $raw, bool $default = true): bool
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

/** When true (default), finished jar stock-in deducts the raw bag pool. */
function dept_jar_cacao_setting_bag_pool_deduct_enabled(?PDO $pdo = null): bool
{
    if ($pdo === null) {
        return true;
    }

    return dept_jar_cacao_setting_parse_bool(
        app_setting_get($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, null),
        true
    );
}

function dept_jar_cacao_setting_grams_per_jar(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) JAR_CACAO_GRAMS_PER_JAR;
    }
    $raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) JAR_CACAO_GRAMS_PER_JAR;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < JAR_CACAO_GRAMS_PER_JAR_MIN || $n > JAR_CACAO_GRAMS_PER_JAR_MAX) {
        return (float) JAR_CACAO_GRAMS_PER_JAR;
    }

    return $n;
}

function dept_jar_cacao_setting_grams_per_bag(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return (float) JAR_CACAO_GRAMS_PER_BAG;
    }
    $raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return (float) JAR_CACAO_GRAMS_PER_BAG;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < JAR_CACAO_GRAMS_PER_BAG_MIN || $n > JAR_CACAO_GRAMS_PER_BAG_MAX) {
        return (float) JAR_CACAO_GRAMS_PER_BAG;
    }

    return $n;
}

function dept_jar_cacao_setting_freebie_grams(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return JAR_CACAO_FREEBIE_GRAMS_DEFAULT;
    }
    $raw = app_setting_get($pdo, JAR_CACAO_FREEBIE_GRAMS_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return JAR_CACAO_FREEBIE_GRAMS_DEFAULT;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < JAR_CACAO_FREEBIE_GRAMS_MIN || $n > JAR_CACAO_FREEBIE_GRAMS_MAX) {
        return JAR_CACAO_FREEBIE_GRAMS_DEFAULT;
    }

    return $n;
}

/** Fraction of one supplier bag consumed by one jar. */
function dept_jar_cacao_setting_bags_per_jar(?PDO $pdo = null): float
{
    $gJar = dept_jar_cacao_setting_grams_per_jar($pdo);
    $gBag = dept_jar_cacao_setting_grams_per_bag($pdo);
    if ($gJar <= 0 || $gBag <= 0) {
        return dept_jar_cacao_bags_per_jar();
    }

    return round($gJar / $gBag, 8);
}

/**
 * @return array{
 *   grams_per_jar: float,
 *   grams_per_bag: float,
 *   jars_per_bag: float,
 *   bags_per_jar: float,
 *   bag_pool_deduct: bool,
 *   freebie_grams: float,
 *   default_grams_per_jar: float,
 *   default_grams_per_bag: float,
 *   default_freebie_grams: float
 * }
 */
function dept_jar_cacao_bom_snapshot(?PDO $pdo = null): array
{
    $gJar = dept_jar_cacao_setting_grams_per_jar($pdo);
    $gBag = dept_jar_cacao_setting_grams_per_bag($pdo);

    return [
        'grams_per_jar' => $gJar,
        'grams_per_bag' => $gBag,
        'jars_per_bag' => $gJar > 0 ? round($gBag / $gJar, 4) : 0.0,
        'bags_per_jar' => dept_jar_cacao_setting_bags_per_jar($pdo),
        'bag_pool_deduct' => dept_jar_cacao_setting_bag_pool_deduct_enabled($pdo),
        'freebie_grams' => dept_jar_cacao_setting_freebie_grams($pdo),
        'default_grams_per_jar' => (float) JAR_CACAO_GRAMS_PER_JAR,
        'default_grams_per_bag' => (float) JAR_CACAO_GRAMS_PER_BAG,
        'default_freebie_grams' => JAR_CACAO_FREEBIE_GRAMS_DEFAULT,
    ];
}

/**
 * @return array{grams_per_jar: float, grams_per_bag: float, jars_per_bag: float, bag_pool_deduct: bool, freebie_grams: float}
 */
function dept_jar_cacao_bom_save(
    PDO $pdo,
    float $gramsPerJar,
    float $gramsPerBag,
    bool $bagPoolDeduct = true,
    ?int $userId = null,
    ?float $freebieGrams = null
): array {
    if (!is_finite($gramsPerJar) || $gramsPerJar < JAR_CACAO_GRAMS_PER_JAR_MIN || $gramsPerJar > JAR_CACAO_GRAMS_PER_JAR_MAX) {
        throw new RuntimeException(
            'Grams per jar must be between ' . JAR_CACAO_GRAMS_PER_JAR_MIN . ' and ' . JAR_CACAO_GRAMS_PER_JAR_MAX . '.'
        );
    }
    if (!is_finite($gramsPerBag) || $gramsPerBag < JAR_CACAO_GRAMS_PER_BAG_MIN || $gramsPerBag > JAR_CACAO_GRAMS_PER_BAG_MAX) {
        throw new RuntimeException(
            'Grams per bag must be between ' . JAR_CACAO_GRAMS_PER_BAG_MIN . ' and ' . JAR_CACAO_GRAMS_PER_BAG_MAX . '.'
        );
    }
    if ($gramsPerJar > $gramsPerBag) {
        throw new RuntimeException('Grams per jar cannot exceed grams per bag.');
    }
    $freebie = $freebieGrams !== null ? $freebieGrams : dept_jar_cacao_setting_freebie_grams($pdo);
    if (!is_finite($freebie) || $freebie < JAR_CACAO_FREEBIE_GRAMS_MIN || $freebie > JAR_CACAO_FREEBIE_GRAMS_MAX) {
        throw new RuntimeException(
            'Freebie grams must be between ' . JAR_CACAO_FREEBIE_GRAMS_MIN . ' and ' . JAR_CACAO_FREEBIE_GRAMS_MAX . '.'
        );
    }
    app_setting_set($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, (string) $gramsPerJar, $userId);
    app_setting_set($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);
    app_setting_set($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);
    app_setting_set($pdo, JAR_CACAO_FREEBIE_GRAMS_SETTING_KEY, (string) $freebie, $userId);

    $snap = dept_jar_cacao_bom_snapshot($pdo);

    return [
        'grams_per_jar' => $snap['grams_per_jar'],
        'grams_per_bag' => $snap['grams_per_bag'],
        'jars_per_bag' => $snap['jars_per_bag'],
        'bag_pool_deduct' => $snap['bag_pool_deduct'],
        'freebie_grams' => $snap['freebie_grams'],
    ];
}

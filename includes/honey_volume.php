<?php

declare(strict_types=1);

require_once __DIR__ . '/app_settings.php';

/** US fluid gallon in milliliters (factory default; override via app_settings honey.ml_per_gallon). */
const HONEY_ML_PER_US_GALLON = 3785.411784;

/** US fluid gallon in liters (factory default). */
const HONEY_LITERS_PER_US_GALLON = HONEY_ML_PER_US_GALLON / 1000.0;

const HONEY_ML_PER_GALLON_SETTING_KEY = 'honey.ml_per_gallon';
const HONEY_PACKAGING_DEDUCT_SETTING_KEY = 'honey.packaging_deduct';
const HONEY_ML_PER_GALLON_MIN = 1000.0;
const HONEY_ML_PER_GALLON_MAX = 100000.0;

function honey_setting_parse_bool(?string $raw, bool $default = true): bool
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

/**
 * When true (default), bottling / bottle barcode stock-in deducts empty bottles + stickers
 * from Packaging Materials (`stocks.on_hand`).
 */
function honey_packaging_deduct_enabled(?PDO $pdo = null): bool
{
    if ($pdo === null) {
        return true;
    }

    return honey_setting_parse_bool(app_setting_get($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, null), true);
}

function honey_packaging_deduct_save(PDO $pdo, bool $enabled, ?int $userId = null): bool
{
    app_setting_set($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, $enabled ? '1' : '0', $userId);

    return $enabled;
}

function honey_price_per_liter_from_gallon(float $pricePerGallon): float
{
    return $pricePerGallon / HONEY_LITERS_PER_US_GALLON;
}

/**
 * Resolve ml per production gallon (settings override, else factory default).
 */
function honey_ml_per_gallon(?PDO $pdo = null): float
{
    if ($pdo === null) {
        return HONEY_ML_PER_US_GALLON;
    }
    $raw = app_setting_get($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, null);
    if ($raw === null || $raw === '') {
        return HONEY_ML_PER_US_GALLON;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < HONEY_ML_PER_GALLON_MIN || $n > HONEY_ML_PER_GALLON_MAX) {
        return HONEY_ML_PER_US_GALLON;
    }

    return $n;
}

/**
 * Validate and persist ml-per-gallon setting.
 */
function honey_ml_per_gallon_save(PDO $pdo, float $ml, ?int $userId = null): float
{
    if (!is_finite($ml) || $ml < HONEY_ML_PER_GALLON_MIN || $ml > HONEY_ML_PER_GALLON_MAX) {
        throw new RuntimeException(
            'ml per gallon must be between ' . (int) HONEY_ML_PER_GALLON_MIN . ' and ' . (int) HONEY_ML_PER_GALLON_MAX . '.'
        );
    }
    // Store with enough precision for fractional ml.
    $stored = rtrim(rtrim(sprintf('%.6F', $ml), '0'), '.');
    app_setting_set($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, $stored, $userId);

    return (float) $stored;
}

const HONEY_BOTTLE_SIZE_KEYS = ['250ml', '350ml', 'liter', 'gallon'];

function honey_normalize_bottle_size(string $size): string
{
    $size = strtolower(trim($size));
    if ($size === '1l' || $size === '1 liter' || $size === '1000ml') {
        return 'liter';
    }
    if (!in_array($size, HONEY_BOTTLE_SIZE_KEYS, true)) {
        throw new RuntimeException('Invalid bottle size.');
    }

    return $size;
}

function honey_bottle_size_ml(string $size, ?float $mlPerGallon = null): float
{
    $gal = $mlPerGallon ?? HONEY_ML_PER_US_GALLON;

    return match (honey_normalize_bottle_size($size)) {
        '250ml' => 250.0,
        '350ml' => 350.0,
        'liter' => 1000.0,
        'gallon' => $gal,
        default => throw new RuntimeException('Invalid bottle size.'),
    };
}

/**
 * @param list<array{size:string, qty:float|int|string}> $lines
 * @return array{liter:float, 250ml:float, 350ml:float, gallon:float}
 */
function honey_aggregate_bottle_lines(array $lines): array
{
    $out = ['liter' => 0.0, '250ml' => 0.0, '350ml' => 0.0, 'gallon' => 0.0];
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $size = honey_normalize_bottle_size((string) ($line['size'] ?? ''));
        $qty = (float) ($line['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $out[$size] += $qty;
    }

    return $out;
}

/**
 * @param list<array{size:string, qty:float|int|string}> $lines
 */
function honey_lines_total_ml(array $lines, ?float $mlPerGallon = null): float
{
    $total = 0.0;
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $qty = (float) ($line['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''), $mlPerGallon);
    }

    return $total;
}

function honey_ml_to_gallons(float $ml, ?float $mlPerGallon = null): float
{
    if ($ml <= 0) {
        return 0.0;
    }
    $denom = $mlPerGallon ?? HONEY_ML_PER_US_GALLON;
    if ($denom <= 0) {
        $denom = HONEY_ML_PER_US_GALLON;
    }

    return $ml / $denom;
}

/**
 * @param list<array{size:string, qty:float|int|string}> $lines
 */
function honey_lines_to_gallons(array $lines, ?float $mlPerGallon = null): float
{
    return honey_ml_to_gallons(honey_lines_total_ml($lines, $mlPerGallon), $mlPerGallon);
}

/**
 * Parse API payload lines or legacy size_qtys map.
 *
 * @return list<array{size:string, qty:float}>
 */
function honey_parse_bottle_lines_from_input(array $input): array
{
    if (isset($input['lines']) && is_array($input['lines'])) {
        $lines = [];
        foreach ($input['lines'] as $line) {
            if (!is_array($line)) {
                continue;
            }
            $qty = (float) ($line['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $lines[] = [
                'size' => honey_normalize_bottle_size((string) ($line['size'] ?? '')),
                'qty' => $qty,
            ];
        }

        return $lines;
    }

    $sizeQtys = is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [];
    $lines = [];
    foreach ($sizeQtys as $size => $qty) {
        $qty = (float) $qty;
        if ($qty <= 0) {
            continue;
        }
        $lines[] = [
            'size' => honey_normalize_bottle_size((string) $size),
            'qty' => $qty,
        ];
    }

    return $lines;
}

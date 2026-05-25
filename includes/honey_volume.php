<?php

declare(strict_types=1);

/** US fluid gallon in milliliters (exact; use only in calculations). */
const HONEY_ML_PER_US_GALLON = 3785.411784;

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

function honey_bottle_size_ml(string $size): float
{
    return match (honey_normalize_bottle_size($size)) {
        '250ml' => 250.0,
        '350ml' => 350.0,
        'liter' => 1000.0,
        'gallon' => HONEY_ML_PER_US_GALLON,
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

/** @param list<array{size:string, qty:float|int|string}> $lines */
function honey_lines_total_ml(array $lines): float
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
        $total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''));
    }

    return $total;
}

function honey_ml_to_gallons(float $ml): float
{
    if ($ml <= 0) {
        return 0.0;
    }

    return $ml / HONEY_ML_PER_US_GALLON;
}

/** @param list<array{size:string, qty:float|int|string}> $lines */
function honey_lines_to_gallons(array $lines): float
{
    return honey_ml_to_gallons(honey_lines_total_ml($lines));
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

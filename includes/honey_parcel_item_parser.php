<?php

declare(strict_types=1);

/**
 * Parse CSR / marketplace Item/s strings into packaging deduct lines.
 * Mirrors app/src/app/lib/orderRemarksParse.ts and pdf-to-excel/extractor.py rules.
 *
 * Multi-item lines use `;` or `+` (e.g. "3 Paragis Tea ; 1 Liter Honey AMBER").
 * Non-honey products are noted in other_items (inventory TBD) without blocking honey deduct.
 */

const HONEY_PARCEL_VALID_3L = ['LAD', 'LLL', 'AAA', 'DDD', 'LAA', 'LLD', 'ADD', 'AAD'];
const HONEY_PARCEL_VALID_2L = ['LA', 'LD', 'AD', 'LL', 'AA', 'DD'];
const HONEY_PARCEL_VALID_1L = ['L', 'A', 'D'];

/**
 * @return array{
 *   deltas: list<array{variant: string, size: string, qty: float}>,
 *   other_items: list<array{label: string, qty: float, raw: string}>,
 *   errors: list<string>
 * }
 */
function honey_parcel_parse_items_string(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['deltas' => [], 'other_items' => [], 'errors' => ['Item/s is empty.']];
    }

    $segments = preg_split('/\s*[;+]\s*/', $raw) ?: [$raw];
    $deltas = [];
    $otherItems = [];
    $errors = [];

    foreach ($segments as $segment) {
        $segment = trim($segment);
        if ($segment === '') {
            continue;
        }
        $part = honey_parcel_parse_segment($segment);
        foreach ($part['errors'] as $err) {
            $errors[] = $err;
        }
        foreach ($part['deltas'] as $d) {
            $deltas[] = $d;
        }
        foreach ($part['other_items'] as $o) {
            $otherItems[] = $o;
        }
    }

    if ($deltas === [] && $otherItems === [] && $errors === []) {
        $errors[] = 'No items recognized in: ' . mb_substr($raw, 0, 120);
    }

    return [
        'deltas' => honey_parcel_merge_deltas($deltas),
        'other_items' => honey_parcel_merge_other_items($otherItems),
        'errors' => array_values(array_unique($errors)),
    ];
}

/**
 * @return array{
 *   deltas: list<array{variant: string, size: string, qty: float}>,
 *   other_items: list<array{label: string, qty: float, raw: string}>,
 *   errors: list<string>
 * }
 */
function honey_parcel_parse_segment(string $segment): array
{
    $qty = honey_parcel_extract_qty($segment);
    $errors = [];
    $emptyOther = ['deltas' => [], 'other_items' => [], 'errors' => []];

    if (preg_match('/350\s*ml|B2T1/i', $segment)) {
        $code = honey_parcel_extract_350_code($segment);
        if ($code === null) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['350ml line needs variant (L/A/D): ' . $segment]];
        }
        $variant = honey_parcel_letter_to_variant($code);

        return [
            'deltas' => [['variant' => $variant, 'size' => '350ml', 'qty' => $qty]],
            'other_items' => [],
            'errors' => [],
        ];
    }

    $otherLabel = honey_parcel_detect_other_product($segment);
    if ($otherLabel !== null && !preg_match('/honey/i', $segment)) {
        return [
            'deltas' => [],
            'other_items' => [['label' => $otherLabel, 'qty' => $qty, 'raw' => trim($segment)]],
            'errors' => [],
        ];
    }

    $product = honey_parcel_detect_product($segment);
    if ($product === null) {
        if (preg_match('/honey/i', $segment)) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['Unresolved honey line (variant required): ' . $segment]];
        }

        return $emptyOther;
    }

    if ($product === 'gallon') {
        $variant = honey_parcel_detect_word_variant($segment);
        if ($variant === null) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['Gallon line needs Light/Amber/Dark: ' . $segment]];
        }

        return [
            'deltas' => [['variant' => $variant, 'size' => 'gallon', 'qty' => $qty]],
            'other_items' => [],
            'errors' => [],
        ];
    }

    $code = honey_parcel_resolve_main_code($segment, $product);
    if ($code === null) {
        $variant = honey_parcel_detect_word_variant($segment);
        if ($variant !== null && $product === '1L') {
            return [
                'deltas' => [['variant' => $variant, 'size' => 'liter', 'qty' => $qty]],
                'other_items' => [],
                'errors' => [],
            ];
        }

        return ['deltas' => [], 'other_items' => [], 'errors' => ['Could not resolve variant for: ' . $segment]];
    }

    $letters = honey_parcel_code_to_letters($code, $product);
    $deltas = [];
    foreach ($letters as $letter) {
        $deltas[] = [
            'variant' => honey_parcel_letter_to_variant($letter),
            'size' => 'liter',
            'qty' => $qty,
        ];
    }

    return ['deltas' => $deltas, 'other_items' => [], 'errors' => $errors];
}

function honey_parcel_extract_qty(string $segment): float
{
    if (preg_match('/^(\d+)\s*x\s*/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }
    if (preg_match('/^(\d+)\s+/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }

    return 1.0;
}

/**
 * Known non-honey catalog lines (not in packaging inventory yet — note only).
 */
function honey_parcel_detect_other_product(string $segment): ?string
{
    if (preg_match('/paragis/i', $segment)) {
        return 'Paragis Tea';
    }
    if (preg_match('/\bcacao|tablea/i', $segment)) {
        return 'Cacao';
    }
    if (preg_match('/black\s*rice\s*coffee|\bbrc\b/i', $segment)) {
        return 'Black Rice Coffee';
    }
    if (preg_match('/pito[\s-]*pito/i', $segment)) {
        return 'Pito-Pito';
    }
    if (preg_match('/honey\s*comb|honeycomb/i', $segment)) {
        return 'Honey Comb';
    }

    return null;
}

function honey_parcel_detect_product(string $segment): ?string
{
    if (preg_match('/gallon/i', $segment)) {
        return 'gallon';
    }
    if (preg_match('/3\s*L|3L-HONEY|3L HONEY/i', $segment)) {
        return '3L';
    }
    if (preg_match('/2\s*L|2L-HONEY|2L HONEY/i', $segment)) {
        return '2L';
    }
    if (preg_match('/1\s*L|1L HONEY|liter/i', $segment)) {
        return '1L';
    }

    return null;
}

function honey_parcel_extract_350_code(string $segment): ?string
{
    if (preg_match('/350\s*ml\s*\(\s*([LAD]+)\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }
    if (preg_match('/350\s*ml\.?\s*Honey\s*\(\s*([LAD]+)\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }
    if (preg_match('/\(\s*([LAD])\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }

    return null;
}

function honey_parcel_resolve_main_code(string $segment, string $product): ?string
{
    if ($product === '3L' && preg_match('/3\s*L\s*Honey\s*\(\s*([LAD]{3})\s*\)/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_3L, true) ? $c : null;
    }
    if ($product === '2L' && preg_match('/2\s*L\s*Honey\s*\(\s*([LAD]{2})\s*\)/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_2L, true) ? $c : null;
    }
    if ($product === '1L' && preg_match('/1\s*L\s*Honey\s*\(\s*([LAD])\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }

    if ($product === '3L' && preg_match('/3L-HONEY\s+([LAD]{3})/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_3L, true) ? $c : null;
    }
    if ($product === '2L' && preg_match('/2L-HONEY\s+([LAD]{2})/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_2L, true) ? $c : null;
    }
    if ($product === '1L' && preg_match('/1L HONEY\s+([LAD])/i', $segment, $m)) {
        return strtoupper($m[1]);
    }

    if (preg_match('/1L\s*HONEY\s+([LAD])\b/i', $segment, $m)) {
        return strtoupper($m[1]);
    }
    if (preg_match('/2L-HONEY\s+([LAD]{2})/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_2L, true) ? $c : null;
    }
    if (preg_match('/3L-HONEY\s+([LAD]{3})/i', $segment, $m)) {
        $c = strtoupper($m[1]);

        return in_array($c, HONEY_PARCEL_VALID_3L, true) ? $c : null;
    }

    if (preg_match('/\(\s*([LAD]{1,3})\s*\)/i', $segment, $m)) {
        $c = strtoupper($m[1]);
        if ($product === '3L' && in_array($c, HONEY_PARCEL_VALID_3L, true)) {
            return $c;
        }
        if ($product === '2L' && in_array($c, HONEY_PARCEL_VALID_2L, true)) {
            return $c;
        }
        if ($product === '1L' && in_array($c, HONEY_PARCEL_VALID_1L, true)) {
            return $c;
        }
    }

    if (preg_match('/\bLAD\b/i', $segment) && $product === '3L') {
        return 'LAD';
    }

    return null;
}

/** @return list<string> single-letter variants */
function honey_parcel_code_to_letters(string $code, string $product): array
{
    $code = strtoupper($code);
    if ($product === '3L') {
        return str_split($code);
    }
    if ($product === '2L') {
        return str_split($code);
    }

    return [$code];
}

function honey_parcel_detect_word_variant(string $segment): ?string
{
    if (preg_match('/\bamber\b/i', $segment)) {
        return 'amber';
    }
    if (preg_match('/\blight\b/i', $segment)) {
        return 'light';
    }
    if (preg_match('/\bdark\b/i', $segment)) {
        return 'dark';
    }

    return null;
}

function honey_parcel_letter_to_variant(string $letter): string
{
    return match (strtoupper($letter)) {
        'L' => 'light',
        'A' => 'amber',
        'D' => 'dark',
        default => 'other',
    };
}

/**
 * @param list<array{variant: string, size: string, qty: float}> $deltas
 * @return list<array{variant: string, size: string, qty: float}>
 */
function honey_parcel_merge_deltas(array $deltas): array
{
    $map = [];
    foreach ($deltas as $d) {
        $v = (string) ($d['variant'] ?? '');
        $s = (string) ($d['size'] ?? '');
        $q = (float) ($d['qty'] ?? 0);
        if ($v === '' || $s === '' || $q <= 0 || $v === 'other') {
            continue;
        }
        $key = $v . '|' . $s;
        $map[$key] = ($map[$key] ?? 0) + $q;
    }
    $out = [];
    foreach ($map as $key => $qty) {
        [$v, $s] = explode('|', $key, 2);
        $out[] = ['variant' => $v, 'size' => $s, 'qty' => round($qty, 4)];
    }

    return $out;
}

/**
 * @param list<array{label: string, qty: float, raw: string}> $items
 * @return list<array{label: string, qty: float, raw: string}>
 */
function honey_parcel_merge_other_items(array $items): array
{
    $map = [];
    foreach ($items as $item) {
        $label = (string) ($item['label'] ?? '');
        $q = (float) ($item['qty'] ?? 0);
        if ($label === '' || $q <= 0) {
            continue;
        }
        if (!isset($map[$label])) {
            $map[$label] = ['label' => $label, 'qty' => 0.0, 'raw' => (string) ($item['raw'] ?? $label)];
        }
        $map[$label]['qty'] += $q;
    }
    $out = [];
    foreach ($map as $row) {
        $row['qty'] = round($row['qty'], 4);
        $out[] = $row;
    }

    return $out;
}

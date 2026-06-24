<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_products.php';

/**
 * Parse CSR / marketplace Item/s strings into packaging deduct lines.
 * Mirrors app/src/app/lib/orderRemarksParse.ts and pdf-to-excel/extractor.py rules.
 *
 * Multi-item lines use `;` or `+` (e.g. "3 Paragis Tea ; 1 Liter Honey AMBER").
 * Non-honey dept catalog products are noted in other_items (with product_key when known).
 */

const HONEY_PARCEL_VALID_3L = ['LAD', 'LLL', 'AAA', 'DDD', 'LLA', 'LAA', 'LLD', 'ADD', 'AAD'];
const HONEY_PARCEL_VALID_2L = ['LA', 'LD', 'AD', 'LL', 'AA', 'DD'];
const HONEY_PARCEL_VALID_1L = ['L', 'A', 'D'];

/**
 * @return array{
 *   deltas: list<array{variant: string, size: string, qty: float}>,
 *   other_items: list<array{label: string, qty: float, raw: string}>,
 *   errors: list<string>
 * }
 */
function honey_parcel_split_jnt_concatenated_bulk_ml(string $part): array
{
    if (!preg_match('/^(.*?)(\d+\s*PC\s*(?:250|350)\s*ML.*)$/i', $part, $m)) {
        return [$part];
    }
    $head = trim($m[1]);
    $tail = trim($m[2]);
    if ($head === '' || $tail === '') {
        return [$part];
    }
    if (!preg_match('/\bhoney|3\s*L|2\s*L|1\s*L|3L|2L|1L/i', $head)) {
        return [$part];
    }

    return [$head, $tail];
}

function honey_parcel_split_segments(string $raw): array
{
    $parts = preg_split('/\s*[;+]\s*/', $raw) ?: [$raw];
    $expanded = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $jntParts = honey_parcel_split_jnt_concatenated_bulk_ml($part);
        foreach ($jntParts as $jntPart) {
            if (preg_match('/,\s*(?:\d+\s*x\s*|\d+\s*(?:JAR|PARAGIS|BLACK|HONEY|250|350|G\s|GALLON|PC|L[\s-]?HONEY))/i', $jntPart)) {
                $sub = preg_split('/\s*,\s*(?=\d+\s*x\s*|\d+\s*(?:JAR|PARAGIS|BLACK|HONEY|250|350|G\s|GALLON|PC|L[\s-]?HONEY))/i', $jntPart) ?: [$jntPart];
                foreach ($sub as $s) {
                    $s = trim($s);
                    if ($s !== '') {
                        $expanded[] = $s;
                    }
                }
            } else {
                $expanded[] = $jntPart;
            }
        }
    }

    return $expanded;
}

function honey_parcel_promo_bottle_count(string $segment): ?int
{
    if (preg_match('/B2T1|BUY\s*2\s*TAKE\s*1/i', $segment)) {
        return 3;
    }
    if (preg_match('/B1T2|BUY\s*1\s*TAKE\s*2/i', $segment)) {
        return 3;
    }
    if (preg_match('/(?:^|\s)(\d+)\s*[x×]\s*250/i', $segment, $m)) {
        return max(1, (int) $m[1]);
    }
    if (preg_match('/(?:^|\s)(\d+)\s*PC\s*350\s*ml/i', $segment, $m)) {
        return max(1, (int) $m[1]);
    }
    if (preg_match('/(?:^|\s)(\d+)\s*PC\s*250\s*ml/i', $segment, $m)) {
        return max(1, (int) $m[1]);
    }

    return null;
}

function honey_parcel_parse_items_string(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['deltas' => [], 'other_items' => [], 'errors' => ['Item/s is empty.']];
    }

    $segments = honey_parcel_split_segments($raw);
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

    if (preg_match('/250\s*ml/i', $segment)) {
        $code = honey_parcel_extract_bulk_ml_code($segment, '250');
        if ($code === null) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['250ml line needs variant (L/A/D): ' . $segment]];
        }
        $code = strtoupper($code);
        $letters = str_split($code);
        $perBottle = count($letters) > 1 ? 1.0 : $qty;
        $deltas = [];
        foreach ($letters as $letter) {
            $variant = honey_parcel_letter_to_variant($letter);
            if ($variant === 'other') {
                continue;
            }
            $deltas[] = ['variant' => $variant, 'size' => '250ml', 'qty' => $perBottle];
        }
        if ($deltas === []) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['250ml line needs variant (L/A/D): ' . $segment]];
        }

        return [
            'deltas' => $deltas,
            'other_items' => [],
            'errors' => [],
        ];
    }

    if (preg_match('/350\s*ml|B2T1|B1T2/i', $segment)) {
        $code = honey_parcel_extract_bulk_ml_code($segment, '350');
        if ($code === null) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['350ml line needs variant (L/A/D): ' . $segment]];
        }
        $code = strtoupper($code);
        $letters = str_split($code);
        $perBottle = count($letters) > 1 ? 1.0 : $qty;
        $deltas = [];
        foreach ($letters as $letter) {
            $variant = honey_parcel_letter_to_variant($letter);
            if ($variant === 'other') {
                continue;
            }
            $deltas[] = ['variant' => $variant, 'size' => '350ml', 'qty' => $perBottle];
        }
        if ($deltas === []) {
            return ['deltas' => [], 'other_items' => [], 'errors' => ['350ml line needs variant (L/A/D): ' . $segment]];
        }

        return [
            'deltas' => $deltas,
            'other_items' => [],
            'errors' => [],
        ];
    }

    $other = honey_parcel_detect_other_product($segment);
    if ($other !== null && !preg_match('/honey|\d\s*L|250\s*ml|350\s*ml|gallon|g\s*honey/i', $segment)) {
        $deptQty = honey_parcel_extract_dept_qty($segment);
        $item = [
            'label' => $other['label'],
            'qty' => $deptQty,
            'raw' => trim($segment),
        ];
        if ($other['product_key'] !== null) {
            $item['product_key'] = $other['product_key'];
        }

        return [
            'deltas' => [],
            'other_items' => [$item],
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

function honey_parcel_extract_dept_qty(string $segment): float
{
    if (preg_match('/\b(?:paragis(?:\s*tea)?|jar\s*cacao|black\s*rice\s*coffee|honey\s*comb|\bbrc\b)\s*[x×]\s*(\d+)\s*$/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }
    if (preg_match('/^1\s*[x×]\s*(\d+)\s+(?:paragis|jar\s*cacao|black\s*rice|honey\s*comb|\bbrc\b|pito)/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }
    if (preg_match('/^(\d+)\s+(?:paragis|jar\s*cacao|black\s*rice|honey\s*comb|\bbrc\b|pito)/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }

    return honey_parcel_extract_qty($segment);
}

function honey_parcel_extract_qty(string $segment): float
{
    $promo = honey_parcel_promo_bottle_count($segment);
    if ($promo !== null && preg_match('/250\s*ml|350\s*ml|B2T1|B1T2/i', $segment)) {
        return (float) $promo;
    }
    if (preg_match('/^(\d+)\s*[x×]\s*/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }
    if (preg_match('/^(\d+)\s+/i', $segment, $m)) {
        return max(1.0, (float) $m[1]);
    }

    return 1.0;
}

/**
 * Known non-honey lines. Dept catalog products include product_key for deduct.
 *
 * @return array{label: string, product_key: ?string}|null
 */
function honey_parcel_detect_other_product(string $segment): ?array
{
    $deptDef = dept_product_resolve_segment($segment);
    if ($deptDef !== null) {
        return ['label' => $deptDef['label'], 'product_key' => $deptDef['key']];
    }
    if (preg_match('/pito[\s-]*pito/i', $segment)) {
        return ['label' => 'Pito-Pito', 'product_key' => null];
    }
    if (preg_match('/honey\s*comb|honeycomb/i', $segment)) {
        return ['label' => 'Honey Comb', 'product_key' => 'honey_comb'];
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

function honey_parcel_extract_bulk_ml_code(string $segment, string $ml): ?string
{
    $ml = $ml === '250' ? '250' : '350';
    if (preg_match('/' . $ml . '\s*ml\s*\(\s*([LAD]+)\s*\)/i', $segment, $m)) {
        $code = strtoupper($m[1]);
        if (preg_match('/^[LAD]+$/', $code)) {
            return $code;
        }
    }
    if (preg_match('/' . $ml . '\s*ml\s*\(\s*(Light|Amber|Dark)\s*\)/i', $segment, $m)) {
        return strtoupper($m[1][0]);
    }
    if (preg_match('/' . $ml . '\s*ml\.?\s*Honey\s*\(\s*([LAD]+)\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }
    if (preg_match('/' . $ml . '\s*ML\s*[:\s/]*([LAD]{3})/i', $segment, $m)) {
        return strtoupper($m[1]);
    }
    if (preg_match('/\bLAD\b/i', $segment) && preg_match('/' . $ml . '/i', $segment)) {
        return 'LAD';
    }
    if (preg_match('/\(\s*([LAD])\s*\)/i', $segment, $m)) {
        return strtoupper($m[1]);
    }

    return null;
}

/** @deprecated Use honey_parcel_extract_bulk_ml_code($segment, '350') */
function honey_parcel_extract_350_code(string $segment): ?string
{
    return honey_parcel_extract_bulk_ml_code($segment, '350');
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
        $key = isset($item['product_key']) ? (string) $item['product_key'] : '';
        $q = (float) ($item['qty'] ?? 0);
        if ($label === '' || $q <= 0) {
            continue;
        }
        $mergeKey = $key !== '' ? 'k:' . $key : 'l:' . $label;
        if (!isset($map[$mergeKey])) {
            $row = ['label' => $label, 'qty' => 0.0, 'raw' => (string) ($item['raw'] ?? $label)];
            if ($key !== '') {
                $row['product_key'] = $key;
            }
            $map[$mergeKey] = $row;
        }
        $map[$mergeKey]['qty'] += $q;
    }
    $out = [];
    foreach ($map as $row) {
        $row['qty'] = round($row['qty'], 4);
        $out[] = $row;
    }

    return $out;
}

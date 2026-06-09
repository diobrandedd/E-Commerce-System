<?php

declare(strict_types=1);

/**
 * Parse Shopee "Packing List.Standard Local" product_info blobs into packer item lines.
 */

/**
 * @return array{product_name: string, variation_name: string, quantity: int}
 */
function shopee_spx_parse_product_info_fields(string $raw): array
{
    $productName = '';
    $variationName = '';
    $qty = 1;

    if (preg_match('/Product Name:\s*([^;]+)/i', $raw, $m)) {
        $productName = trim($m[1]);
    }
    if (preg_match('/Variation Name:\s*([^;]+)/i', $raw, $m)) {
        $variationName = trim($m[1]);
    }
    if (preg_match('/Quantity:\s*(\d+)/i', $raw, $m)) {
        $qty = max(1, (int) $m[1]);
    }

    return [
        'product_name' => $productName,
        'variation_name' => $variationName,
        'quantity' => $qty,
    ];
}

/**
 * Light / Amber / Dark from Shopee variation or product title (not LAD combo promos).
 */
function shopee_spx_extract_honey_variant(string $variationName, string $productName): ?string
{
    foreach ([$variationName, $productName] as $text) {
        if ($text === '') {
            continue;
        }
        if (preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $text)) {
            continue;
        }
        if (preg_match('/1LITER[-\s]*(Light|Amber|Dark)\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b(Light|Amber|Dark)\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }
    }

    return null;
}

function shopee_spx_product_is_gallon(string $productName, string $variationName): bool
{
    return preg_match('/\bgallon\b/i', $productName . ' ' . $variationName) === 1;
}

function shopee_spx_format_gallon_items(int $qty, string $variant): string
{
    $qty = max(1, $qty);
    $variant = strtoupper(trim($variant));

    return $qty . ' Gallon Honey ' . $variant;
}

/**
 * Shopee Quantity field is often order units; packer text should follow size in product title
 * (e.g. "1 Gallon …" + Quantity: 2 → 1 Gallon Honey AMBER, not 2 Gallon).
 *
 * @return array{use_shopee_multiplier: bool, count: int, liter_combo: int|null}
 */
function shopee_spx_packer_qty_from_name(string $productName, int $shopeeQty): array
{
    $productName = trim($productName);
    if (preg_match('/\b(\d+)\s*Gallon\b/i', $productName, $m)) {
        return [
            'use_shopee_multiplier' => false,
            'count' => max(1, (int) $m[1]),
            'liter_combo' => null,
        ];
    }
    if (preg_match('/\b(\d+)\s*L(?:ITER)?\b/i', $productName, $m) && !preg_match('/350\s*ml/i', $productName)) {
        $n = max(1, (int) $m[1]);
        if ($n >= 2) {
            return ['use_shopee_multiplier' => false, 'count' => 1, 'liter_combo' => $n];
        }

        return ['use_shopee_multiplier' => false, 'count' => 1, 'liter_combo' => null];
    }
    if (preg_match('/\b([23])\s*L[-\s]*HONEY/i', $productName, $m)) {
        return [
            'use_shopee_multiplier' => false,
            'count' => 1,
            'liter_combo' => (int) $m[1],
        ];
    }

    return [
        'use_shopee_multiplier' => true,
        'count' => max(1, $shopeeQty),
        'liter_combo' => null,
    ];
}

function shopee_spx_order_line_prefix(string $productName, int $shopeeQty): string
{
    $pack = shopee_spx_packer_qty_from_name($productName, $shopeeQty);

    return $pack['use_shopee_multiplier'] && $shopeeQty > 1 ? $shopeeQty . ' x ' : '';
}

/**
 * B2T1 on 350ml bottles (product title says 350ml) vs B2T1 on 1L (3×1L + free 350ml).
 */
function shopee_spx_is_b2t1_350ml_promo(string $productName, string $variationName): bool
{
    $combined = $productName . ' ' . $variationName;
    if (preg_match('/BUY\s*2\s*TAKE\s*1|B2T1/i', $combined) !== 1) {
        return false;
    }
    if (!preg_match('/350\s*ml/i', $combined)) {
        return false;
    }
    // 1 Liter B2T1 promos may still list ",350ml" on the variation for the free bottle.
    return !preg_match('/\b1\s*L(?:ITER)?\b/i', $productName);
}

function shopee_spx_format_b2t1_350ml_items(int $orderQty, bool $ladCombo, ?string $singleLetter = null): string
{
    $bottles = 3 * max(1, $orderQty);
    if ($ladCombo) {
        return $bottles . '× 350ml (LAD)';
    }
    if ($singleLetter !== null && $singleLetter !== '') {
        return $bottles . '× 350ml (' . strtoupper($singleLetter[0]) . ')';
    }

    return $bottles . '× 350ml';
}

/**
 * Shopee may pack several products in one cell: "[1] Product Name:… [2] Product Name:…".
 *
 * @return list<string>
 */
function shopee_spx_split_product_info_blocks(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    if (!preg_match('/\[\d+\]\s*Product Name:/i', $raw)) {
        return [$raw];
    }

    $chunks = preg_split('/(?=\[\d+\]\s*Product Name:)/i', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $blocks = [];
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk !== '') {
            $blocks[] = $chunk;
        }
    }

    return $blocks !== [] ? $blocks : [$raw];
}

/**
 * @return array{items: string, complete: bool, missing_fields: list<string>}
 */
function shopee_spx_build_items_from_product_info(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['items' => '', 'complete' => false, 'missing_fields' => ['items']];
    }

    $blocks = shopee_spx_split_product_info_blocks($raw);
    if (count($blocks) === 1) {
        return shopee_spx_build_items_from_single_product_info($blocks[0]);
    }

    $itemLines = [];
    $allComplete = true;
    $missing = [];
    foreach ($blocks as $block) {
        $built = shopee_spx_build_items_from_single_product_info($block);
        if ($built['items'] !== '') {
            $itemLines[] = $built['items'];
        }
        if (!$built['complete']) {
            $allComplete = false;
        }
        foreach ($built['missing_fields'] as $mf) {
            if (!in_array($mf, $missing, true)) {
                $missing[] = $mf;
            }
        }
    }

    $items = implode(' ; ', $itemLines);
    if ($items === '') {
        return ['items' => '', 'complete' => false, 'missing_fields' => ['items']];
    }

    return [
        'items' => $items,
        'complete' => $allComplete,
        'missing_fields' => $allComplete ? [] : ($missing !== [] ? $missing : ['items_variant']),
    ];
}

/**
 * Parse one Shopee product_info block (with or without leading [N] index).
 *
 * @return array{items: string, complete: bool, missing_fields: list<string>}
 */
function shopee_spx_build_items_from_single_product_info(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['items' => '', 'complete' => false, 'missing_fields' => ['items']];
    }

    $fields = shopee_spx_parse_product_info_fields($raw);
    $productName = $fields['product_name'];
    $variationName = $fields['variation_name'];
    $qty = $fields['quantity'];
    $packQty = shopee_spx_packer_qty_from_name($productName, $qty);
    $prefix = shopee_spx_order_line_prefix($productName, $qty);

    if (shopee_spx_product_is_gallon($productName, $variationName)) {
        $variant = shopee_spx_extract_honey_variant($variationName, $productName);
        if ($variant !== null) {
            return [
                'items' => shopee_spx_format_gallon_items($packQty['count'], $variant),
                'complete' => true,
                'missing_fields' => [],
            ];
        }

        return [
            'items' => max(1, $packQty['count']) . ' Gallon Honey',
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }

    if ($packQty['liter_combo'] === 2 && preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $variationName)) {
        return [
            'items' => $prefix . '2L-HONEY (LA)',
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }
    if ($packQty['liter_combo'] === 3 && preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $variationName)) {
        return [
            'items' => $prefix . '3L-HONEY (LAD)',
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }

    if (preg_match('/1LITER[-\s]*(Light|Amber|Dark)\b/i', $variationName, $m)) {
        $variant = strtoupper($m[1]);

        return [
            'items' => $prefix . '1 Liter Honey ' . $variant,
            'complete' => true,
            'missing_fields' => [],
        ];
    }

    if (preg_match('/1\s*LITER[-\s]*(Light|Amber|Dark)\b/i', $variationName, $m)) {
        $variant = strtoupper($m[1]);

        return [
            'items' => $prefix . '1 Liter Honey ' . $variant,
            'complete' => true,
            'missing_fields' => [],
        ];
    }

    $isB2t1 = preg_match('/BUY\s*2\s*TAKE\s*1|B2T1/i', $productName . ' ' . $variationName) === 1;
    if ($isB2t1 && shopee_spx_is_b2t1_350ml_promo($productName, $variationName)) {
        if (preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $variationName)) {
            return [
                'items' => shopee_spx_format_b2t1_350ml_items($qty, true),
                'complete' => true,
                'missing_fields' => [],
            ];
        }
        $variant = shopee_spx_extract_honey_variant($variationName, $productName);
        if ($variant !== null) {
            return [
                'items' => shopee_spx_format_b2t1_350ml_items($qty, false, $variant[0]),
                'complete' => true,
                'missing_fields' => [],
            ];
        }

        return [
            'items' => shopee_spx_format_b2t1_350ml_items($qty, false),
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }
    if ($isB2t1 && preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $variationName)) {
        return [
            'items' => $prefix . '3L-HONEY (LAD) + 350ml',
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }

    if (preg_match('/\b(light|amber|dark)\b/i', $variationName) && preg_match('/honey/i', $productName)) {
        if (preg_match('/\b(light|amber|dark)\b/i', $variationName, $m)) {
            $variant = strtoupper($m[1]);
            if (preg_match('/gallon/i', $variationName . ' ' . $productName)) {
                return [
                    'items' => shopee_spx_format_gallon_items($qty, $variant),
                    'complete' => true,
                    'missing_fields' => [],
                ];
            }
            if (preg_match('/350\s*ml/i', $variationName) && !preg_match('/350\s*ml\s*\(\s*[LAD]\s*\)/i', $variationName)) {
                if (
                    preg_match('/3\s*L/i', $variationName . ' ' . $productName)
                    || (preg_match('/\b1\s*L(?:ITER)?\b/i', $productName) && preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $variationName))
                ) {
                    return [
                        'items' => $prefix . '3L-HONEY (LAD) + 350ml',
                        'complete' => false,
                        'missing_fields' => ['items_variant'],
                    ];
                }

                return [
                    'items' => $prefix . '350ml',
                    'complete' => false,
                    'missing_fields' => ['items_variant'],
                ];
            }
            if (preg_match('/1\s*L|liter/i', $variationName . ' ' . $productName)) {
                return [
                    'items' => $prefix . '1 Liter Honey ' . $variant,
                    'complete' => true,
                    'missing_fields' => [],
                ];
            }
        }
    }

    if (preg_match('/honey/i', $productName) && shopee_spx_items_need_variant_resolution($variationName !== '' ? $variationName : $productName)) {
        $snippet = mb_substr($productName, 0, 80);

        return [
            'items' => $prefix . $snippet,
            'complete' => false,
            'missing_fields' => ['items_variant'],
        ];
    }

    $fallback = $productName !== '' ? $productName : mb_substr($raw, 0, 120);

    return [
        'items' => $prefix . $fallback,
        'complete' => !shopee_spx_items_need_variant_resolution($fallback),
        'missing_fields' => shopee_spx_items_need_variant_resolution($fallback) ? ['items_variant'] : [],
    ];
}

function shopee_spx_items_need_variant_resolution(string $itemStr): bool
{
    $s = trim($itemStr);
    if ($s === '') {
        return false;
    }
    if (!preg_match('/honey|350|B2T1/i', $s)) {
        return false;
    }
    if (preg_match('/\d+\s+Gallon\s+Honey\s+(LIGHT|AMBER|DARK)\b/i', $s)) {
        return false;
    }
    if (preg_match('/\d+\s*[×x]\s*350\s*ml\s*\(\s*LAD\s*\)/i', $s)) {
        return false;
    }
    if (preg_match('/\d+\s*[×x]\s*350\s*ml\s*\(\s*[LAD]\s*\)/i', $s)) {
        return false;
    }
    if (preg_match('/1LITER[-\s]*(Light|Amber|Dark)/i', $s)) {
        return false;
    }
    if (preg_match('/1\s*Liter\s+Honey\s+(LIGHT|AMBER|DARK)/i', $s)) {
        return false;
    }
    if (preg_match('/\b(light|amber|dark)\b/i', $s) && !preg_match('/Light\s*&\s*Amber\s*&\s*Dark/i', $s)) {
        return false;
    }
    if (preg_match('/1L\s*HONEY\s+[LAD]\b/i', $s)) {
        return false;
    }
    if (preg_match('/2L-HONEY\s+[LAD]{2}/i', $s)) {
        return false;
    }
    if (preg_match('/3L-HONEY\s+[LAD]{3}/i', $s)) {
        return false;
    }
    if (preg_match('/\bLAD\b/i', $s) && preg_match('/350\s*ml/i', $s) && !preg_match('/350\s*ml\s*\(\s*[LAD]\s*\)/i', $s)) {
        return true;
    }
    if (preg_match('/\bLAD\b/i', $s)) {
        return false;
    }
    if (preg_match('/Liter\s+Honey\s+(LIGHT|AMBER|DARK)/i', $s)) {
        return false;
    }
    if (preg_match('/1\s*L|2\s*L|3\s*L|350|gallon|liter/i', $s)) {
        return true;
    }

    return (bool) preg_match('/honey/i', $s);
}

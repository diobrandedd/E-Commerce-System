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
 * @return array{items: string, complete: bool, missing_fields: list<string>}
 */
function shopee_spx_build_items_from_product_info(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['items' => '', 'complete' => false, 'missing_fields' => ['items']];
    }

    $fields = shopee_spx_parse_product_info_fields($raw);
    $productName = $fields['product_name'];
    $variationName = $fields['variation_name'];
    $qty = $fields['quantity'];
    $prefix = $qty > 1 ? $qty . ' x ' : '';

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
            if (preg_match('/350\s*ml/i', $variationName) && !preg_match('/350\s*ml\s*\(\s*[LAD]\s*\)/i', $variationName)) {
                if (preg_match('/3\s*L|LAD/i', $variationName)) {
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

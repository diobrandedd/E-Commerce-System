<?php

declare(strict_types=1);

function rts_courier_label(string $code): string
{
    return match ($code) {
        'spx' => 'SPX',
        'jnt' => 'J&T',
        'flash' => 'Flash',
        'unknown' => 'Unknown',
        default => $code,
    };
}

function rts_shop_label(string $shop): string
{
    return match ($shop) {
        'shopee' => 'Shopee',
        'lazada' => 'Lazada',
        'tiktok' => 'TikTok',
        'vip' => 'VIP',
        default => $shop,
    };
}

function rts_line_summary(array $row): string
{
    if (($row['kind'] ?? '') === 'honey') {
        $qty = $row['quantity'] ?? '';
        $v = $row['variant'] ?? '';
        $p = $row['pack_size'] ?? '';

        return htmlspecialchars((string) $qty . ' × honey ' . $v . ' (' . $p . ')', ENT_QUOTES, 'UTF-8');
    }
    $names = [
        'paragis_tea' => 'Paragis tea',
        'black_rice_tea' => 'Black rice tea',
        'cacao' => 'Cacao',
        'honey_comb' => 'Honey comb',
        'pito_pito' => 'Pito-pito',
    ];
    $nh = $row['non_honey_product'] ?? '';
    $label = $names[$nh] ?? $nh;

    return htmlspecialchars((string) ($row['quantity'] ?? '') . ' × ' . $label, ENT_QUOTES, 'UTF-8');
}

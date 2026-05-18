<?php

declare(strict_types=1);

/** @return array<string, list<string>> */
function rts_shop_courier_map(): array
{
    return [
        'shopee' => ['jnt', 'spx', 'flash'],
        'lazada' => ['jnt', 'flash'],
        'tiktok' => ['jnt', 'flash'],
        'vip' => ['jnt', 'spx'],
    ];
}

function rts_shop_allows_courier(string $shop, string $courier): bool
{
    $map = rts_shop_courier_map();

    return isset($map[$shop]) && in_array($courier, $map[$shop], true);
}

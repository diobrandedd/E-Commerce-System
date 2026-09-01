<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_pos.php';

function pos_normalize_buyer_name(string $buyerName): string
{
    $buyerName = trim($buyerName);
    if ($buyerName === '') {
        throw new RuntimeException('Customer name is required.');
    }
    if (strlen($buyerName) > 160) {
        throw new RuntimeException('Customer name is too long.');
    }

    return $buyerName;
}

function pos_normalize_seller_name(string $sellerName): string
{
    $sellerName = trim($sellerName);
    if ($sellerName === '') {
        throw new RuntimeException('Seller name is required.');
    }
    if (strlen($sellerName) > 160) {
        throw new RuntimeException('Seller name is too long.');
    }

    return $sellerName;
}

/**
 * @param array{pin_id?: ?int, user_id?: ?int} $actor
 * @return array{sql_cols: string, sql_vals: string, params: list<mixed>}
 */
function pos_sales_seller_insert(PDO $pdo, array $actor, string $sellerName): array
{
    $sellerName = pos_normalize_seller_name($sellerName);
    if (!honey_pos_column_exists($pdo, 'pos_sales', 'seller_name')) {
        throw new RuntimeException('Run sql/migration_phase74_pos_seller_name.sql so POS can record the seller.');
    }

    $pin = isset($actor['pin_id']) ? (int) $actor['pin_id'] : 0;
    $user = isset($actor['user_id']) ? (int) $actor['user_id'] : 0;
    $pinVal = $pin > 0 ? $pin : null;
    $userVal = $user > 0 ? $user : null;

    $cols = ['seller_name'];
    $vals = ['?'];
    $params = [$sellerName];

    if (honey_pos_column_exists($pdo, 'pos_sales', 'sold_by_user_id')) {
        $cols[] = 'sold_by_pin_id';
        $cols[] = 'sold_by_user_id';
        $vals[] = '?';
        $vals[] = '?';
        $params[] = $pinVal;
        $params[] = $userVal;
    } elseif ($pinVal !== null) {
        $cols[] = 'sold_by_pin_id';
        $vals[] = '?';
        $params[] = $pinVal;
    }

    return [
        'sql_cols' => implode(', ', $cols),
        'sql_vals' => implode(', ', $vals),
        'params' => $params,
    ];
}

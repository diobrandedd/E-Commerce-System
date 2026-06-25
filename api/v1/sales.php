<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/honey_sales_report.php';

/**
 * Honey walk-in sales reporting for Executive + Production Manager.
 *
 *   GET ?action=honey_sold&period=day|week|month|year|custom|all&from=&to=&page=1&receipt=&sale_type=all|regular|discount|ar
 */

api_dispatch([
    'honey_sold' => 'api_sales_honey_sold',
]);

function api_sales_require(): void
{
    $user = api_require_ims_user();
    if (!in_array((string) $user['role'], ['executive', 'prod_manager'], true)) {
        api_fail('Forbidden.', 403);
    }
}

function api_sales_honey_sold(): void
{
    api_method_allowed(['GET']);
    api_sales_require();

    $period = trim((string) ($_GET['period'] ?? 'all'));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $receipt = trim((string) ($_GET['receipt'] ?? ''));
    $saleType = trim((string) ($_GET['sale_type'] ?? 'all'));

    try {
        $data = honey_sales_sold_snapshot(
            ims_pdo(),
            $period === 'all' ? null : $period,
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            $page,
            $receipt !== '' ? $receipt : null,
            $saleType !== '' ? $saleType : 'all'
        );
    } catch (InvalidArgumentException $e) {
        api_fail($e->getMessage(), 422);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($data);
}

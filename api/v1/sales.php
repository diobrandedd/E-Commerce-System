<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/honey_sales_report.php';

/**
 * Honey walk-in sales reporting for Executive + Production Manager.
 *
 *   GET ?action=honey_sold&period=day|week|month|year|custom|all&from=&to=&page=1&receipt=&sale_type=all|regular|discount|ar&product=all|honey|pt|brc|jar_cacao|honey_comb
 */

api_dispatch([
    'honey_sold' => 'api_sales_honey_sold',
]);

function api_sales_require(): array
{
    return api_require_permission('sales.view');
}

/**
 * Remove peso fields when the caller lacks sales.view_amounts.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function api_sales_strip_money_fields(array $data): array
{
    if (isset($data['summary']) && is_array($data['summary'])) {
        unset(
            $data['summary']['revenue_regular'],
            $data['summary']['revenue_discounted'],
            $data['summary']['discounts_given'],
            $data['summary']['revenue_ar']
        );
    }

    if (isset($data['checkouts']['rows']) && is_array($data['checkouts']['rows'])) {
        foreach ($data['checkouts']['rows'] as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            unset($row['subtotal_amount'], $row['discount_amount'], $row['total_amount']);
            if (isset($row['items']) && is_array($row['items'])) {
                foreach ($row['items'] as $j => $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    unset(
                        $item['list_unit_price'],
                        $item['unit_price'],
                        $item['line_subtotal'],
                        $item['line_total']
                    );
                    $row['items'][$j] = $item;
                }
            }
            $data['checkouts']['rows'][$i] = $row;
        }
    }

    return $data;
}

function api_sales_honey_sold(): void
{
    api_method_allowed(['GET']);
    $user = api_sales_require();

    $period = trim((string) ($_GET['period'] ?? 'all'));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $receipt = trim((string) ($_GET['receipt'] ?? ''));
    $saleType = trim((string) ($_GET['sale_type'] ?? 'all'));
    $channel = trim((string) ($_GET['channel'] ?? 'all'));
    $product = trim((string) ($_GET['product'] ?? 'all'));

    try {
        $data = honey_sales_sold_snapshot(
            ims_pdo(),
            $period === 'all' ? null : $period,
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            $page,
            $receipt !== '' ? $receipt : null,
            $saleType !== '' ? $saleType : 'all',
            $channel !== '' ? $channel : 'all',
            $product !== '' ? $product : 'all'
        );
    } catch (InvalidArgumentException $e) {
        api_fail($e->getMessage(), 422);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    $canViewAmounts = ims_user_can($user, 'sales.view_amounts');
    if (!$canViewAmounts) {
        $data = api_sales_strip_money_fields($data);
    }
    $data['can_view_amounts'] = $canViewAmounts;

    api_ok($data);
}

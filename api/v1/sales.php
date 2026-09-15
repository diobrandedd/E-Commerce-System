<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/honey_sales_report.php';

/**
 * Honey walk-in sales reporting for Executive + Production Manager.
 *
 *   GET ?action=honey_sold&period=day|week|month|year|custom|all&from=&to=&page=1&receipt=&sale_type=all|regular|discount|ar&product=all|honey|pt|brc|jar_cacao|honey_comb
 *   GET ?action=honey_sold_export&… (same filters; streams .xlsx with Shipped + Walk-in sheets)
 */

api_dispatch([
    'honey_sold' => 'api_sales_honey_sold',
    'honey_sold_export' => 'api_sales_honey_sold_export',
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

/**
 * @return array{period: ?string, from: ?string, to: ?string, receipt: ?string, sale_type: string, channel: string, product: string}
 */
function api_sales_parse_filter_params(): array
{
    $period = trim((string) ($_GET['period'] ?? 'all'));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $receipt = trim((string) ($_GET['receipt'] ?? ''));
    $saleType = trim((string) ($_GET['sale_type'] ?? 'all'));
    $channel = trim((string) ($_GET['channel'] ?? 'all'));
    $product = trim((string) ($_GET['product'] ?? 'all'));

    return [
        'period' => $period === 'all' || $period === '' ? null : $period,
        'from' => $from !== '' ? $from : null,
        'to' => $to !== '' ? $to : null,
        'receipt' => $receipt !== '' ? $receipt : null,
        'sale_type' => $saleType !== '' ? $saleType : 'all',
        'channel' => $channel !== '' ? $channel : 'all',
        'product' => $product !== '' ? $product : 'all',
    ];
}

function api_sales_honey_sold(): void
{
    api_method_allowed(['GET']);
    $user = api_sales_require();

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $f = api_sales_parse_filter_params();

    try {
        $data = honey_sales_sold_snapshot(
            ims_pdo(),
            $f['period'],
            $f['from'],
            $f['to'],
            $page,
            $f['receipt'],
            $f['sale_type'],
            $f['channel'],
            $f['product']
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

function api_sales_honey_sold_export(): void
{
    api_method_allowed(['GET']);
    $user = api_sales_require();
    $f = api_sales_parse_filter_params();
    $canViewAmounts = ims_user_can($user, 'sales.view_amounts');

    try {
        $path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);
    } catch (InvalidArgumentException $e) {
        api_fail($e->getMessage(), 422);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    if ($path === '' || !is_file($path)) {
        api_fail('Could not build Excel export.', 500);
    }

    $filename = 'sales-export-' . date('Ymd-His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: no-store');

    readfile($path);
    @unlink($path);
    exit;
}

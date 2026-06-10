<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/dept_products.php';

/** @return array{products: list<array<string, mixed>>} */
function dept_pos_catalog(PDO $pdo, ?string $deptKey = null): array
{
    $products = [];
    foreach (dept_inventory_catalog_snapshot($pdo, $deptKey) as $item) {
        $sellable = $item['product_id'] !== null
            && $item['selling_price'] !== null
            && (float) $item['selling_price'] > 0;
        $products[] = array_merge($item, [
            'sellable' => $sellable,
            'available_qty' => (float) $item['current_qty'],
        ]);
    }

    return ['products' => $products];
}

/**
 * @param list<array{product_key: string, qty: float}> $items
 * @return array<string, mixed>
 */
function dept_pos_checkout(PDO $pdo, int $posPinId, array $items, string $buyerName, string $buyerCreds): array
{
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    if ($items === []) {
        throw new RuntimeException('Cart is empty.');
    }

    $cleanLines = [];
    $total = 0.0;

    foreach ($items as $entry) {
        $productKey = trim((string) ($entry['product_key'] ?? ''));
        $qty = (float) ($entry['qty'] ?? 0);
        if (!dept_product_key_valid($productKey) || $qty <= 0) {
            throw new RuntimeException('Invalid cart line.');
        }

        $st = $pdo->prepare(
            'SELECT p.id, p.name, p.selling_price
             FROM products p
             WHERE p.dept_product_key = ? AND p.selling_price > 0
             LIMIT 1'
        );
        $st->execute([$productKey]);
        $prod = $st->fetch(PDO::FETCH_ASSOC);
        if (!$prod) {
            $def = dept_product_by_key($productKey);
            throw new RuntimeException(($def['label'] ?? $productKey) . ' is not sellable yet (set selling price).');
        }

        $snapshot = dept_inventory_catalog_snapshot($pdo);
        $onHand = 0.0;
        foreach ($snapshot as $s) {
            if ($s['key'] === $productKey) {
                $onHand = (float) $s['current_qty'];
                break;
            }
        }
        if ($qty > $onHand + 0.0001) {
            $def = dept_product_by_key($productKey);
            throw new RuntimeException(
                'Not enough ' . ($def['label'] ?? $productKey) . " on hand ({$onHand} available)."
            );
        }

        $unit = round((float) $prod['selling_price'], 2);
        $lineTotal = round($unit * $qty, 2);
        $total += $lineTotal;
        $cleanLines[] = [
            'product_id' => (int) $prod['id'],
            'product_key' => $productKey,
            'qty' => $qty,
            'unit_price' => $unit,
            'line_total' => $lineTotal,
            'name' => (string) $prod['name'],
        ];
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $total = round($total, 2);
        $receiptNo = sprintf('RCP-%s', strtoupper(bin2hex(random_bytes(4))));

        $insSale = $pdo->prepare(
            'INSERT INTO pos_sales (
                receipt_no, buyer_name, buyer_credentials, subtotal_amount,
                discount_percent, discount_amount, total_amount, sold_by_pin_id, sale_date
             ) VALUES (?, ?, ?, ?, NULL, 0, ?, ?, NOW())'
        );
        $insSale->execute([
            $receiptNo,
            $buyerName !== '' ? $buyerName : 'Walk-in',
            $buyerCreds === '' ? null : $buyerCreds,
            $total,
            $total,
            $posPinId,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $insLine = $pdo->prepare(
            'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent)
             VALUES (?, ?, ?, ?, ?, ?, NULL)'
        );

        foreach ($cleanLines as $line) {
            dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);
            $insLine->execute([
                $saleId,
                $line['product_id'],
                $line['qty'],
                $line['unit_price'],
                $line['unit_price'],
                $line['line_total'],
            ]);
        }

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'sale_id' => $saleId,
        'receipt_no' => $receiptNo,
        'total' => $total,
        'items' => $cleanLines,
        'buyer_name' => $buyerName !== '' ? $buyerName : 'Walk-in',
    ];
}

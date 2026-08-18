<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/dept_pos.php';
require_once __DIR__ . '/pos_unified.php';

/** @param list<array<string,mixed>> $items */
function pos_discount_cart_is_dept(array $items): bool
{
    if ($items === []) {
        return false;
    }
    $first = $items[0];
    $productKey = trim((string) ($first['product_key'] ?? ''));

    return $productKey !== '' && dept_product_key_valid($productKey);
}

/**
 * @param list<array{product_key: string, product_id?: int, qty: float|int|string}> $items
 * @return array{
 *   items:list<array<string,mixed>>,
 *   subtotal:float,
 *   discount_percent:int,
 *   discount_amount:float,
 *   total:float
 * }
 */
function pos_discount_quote_dept(PDO $pdo, array $items, int $discountPercent): array
{
    if (!in_array($discountPercent, [10, 20], true)) {
        throw new RuntimeException('Discount must be 10% or 20%.');
    }

    $discountFactor = 1 - ($discountPercent / 100);
    $clean = dept_pos_build_cart_lines($pdo, $items, $discountFactor);
    $subtotal = round(array_sum(array_column($clean, 'line_subtotal')), 2);
    $total = round(array_sum(array_column($clean, 'line_total')), 2);
    $discountAmount = round($subtotal - $total, 2);

    return [
        'items' => $clean,
        'subtotal' => $subtotal,
        'discount_percent' => $discountPercent,
        'discount_amount' => $discountAmount,
        'total' => $total,
    ];
}

/**
 * @param list<array{product_id:int, qty:float|int|string, variant?:string, size?:string}> $items
 * @return array{
 *   items:list<array<string,mixed>>,
 *   subtotal:float,
 *   discount_percent:int,
 *   discount_amount:float,
 *   total:float
 * }
 */
function pos_discount_quote_honey(PDO $pdo, array $items, int $discountPercent): array
{
    if (!in_array($discountPercent, [10, 20], true)) {
        throw new RuntimeException('Discount must be 10% or 20%.');
    }
    if ($items === []) {
        throw new RuntimeException('Cart is empty.');
    }

    $productMap = honey_pos_product_map($pdo);
    $discountFactor = 1 - ($discountPercent / 100);
    $clean = [];
    $subtotal = 0.0;

    foreach ($items as $entry) {
        $productId = (int) ($entry['product_id'] ?? 0);
        $qty = (float) ($entry['qty'] ?? 0);
        $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
        $size = trim((string) ($entry['size'] ?? ''));
        if ($productId < 1 || $qty <= 0 || $variant === '' || $size === '') {
            throw new RuntimeException('Invalid cart line.');
        }

        $prod = $productMap[$variant][$size] ?? null;
        if (!$prod || (int) $prod['product_id'] !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        $listUnit = (float) $prod['unit_price'];
        $chargedUnit = round($listUnit * $discountFactor, 2);
        $lineSubtotal = round($listUnit * $qty, 2);
        $lineTotal = round($chargedUnit * $qty, 2);
        $subtotal += $lineSubtotal;

        $clean[] = [
            'product_id' => $productId,
            'variant' => $variant,
            'size' => $size,
            'qty' => $qty,
            'list_unit_price' => $listUnit,
            'unit_price' => $chargedUnit,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineTotal,
            'name' => (string) $prod['name'],
        ];
    }

    $subtotal = round($subtotal, 2);
    $total = round(array_sum(array_column($clean, 'line_total')), 2);
    $discountAmount = round($subtotal - $total, 2);

    return [
        'items' => $clean,
        'subtotal' => $subtotal,
        'discount_percent' => $discountPercent,
        'discount_amount' => $discountAmount,
        'total' => $total,
    ];
}

/**
 * @param list<array<string,mixed>> $items
 * @return array{
 *   items:list<array<string,mixed>>,
 *   subtotal:float,
 *   discount_percent:int,
 *   discount_amount:float,
 *   total:float
 * }
 */
function pos_discount_quote(PDO $pdo, array $items, int $discountPercent): array
{
    if ($items === []) {
        throw new RuntimeException('Cart is empty.');
    }

    if (pos_discount_cart_is_unified($items)) {
        return pos_unified_discount_quote($pdo, $items, $discountPercent);
    }

    return pos_discount_cart_is_dept($items)
        ? pos_discount_quote_dept($pdo, $items, $discountPercent)
        : pos_discount_quote_honey($pdo, $items, $discountPercent);
}

/**
 * @param list<array<string,mixed>> $items
 * @return array<string,mixed>
 */
function pos_discount_request_create(
    PDO $pdo,
    int $posPinId,
    array $items,
    string $buyerName,
    string $buyerCreds,
    int $discountPercent
): array {
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    $buyerName = trim($buyerName);
    if ($buyerName === '') {
        throw new RuntimeException('Customer name is required for discount requests.');
    }

    $quote = pos_discount_quote($pdo, $items, $discountPercent);
    $requestNo = sprintf('DRQ-%s', strtoupper(bin2hex(random_bytes(4))));

    $ins = $pdo->prepare(
        'INSERT INTO pos_discount_requests (
            request_no, status, sold_by_pin_id, buyer_name, buyer_credentials, discount_percent,
            subtotal_amount, discount_amount, total_amount, cart_json
         ) VALUES (?, "pending", ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $requestNo,
        $posPinId,
        $buyerName,
        $buyerCreds !== '' ? $buyerCreds : null,
        $discountPercent,
        $quote['subtotal'],
        $quote['discount_amount'],
        $quote['total'],
        json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $requestId = (int) $pdo->lastInsertId();

    return [
        'request_id' => $requestId,
        'request_no' => $requestNo,
        'status' => 'pending',
        'buyer_name' => $buyerName,
        'discount_percent' => $discountPercent,
        'subtotal' => $quote['subtotal'],
        'discount_amount' => $quote['discount_amount'],
        'total' => $quote['total'],
        'items' => $quote['items'],
    ];
}

/** @return array<string,mixed> */
function pos_discount_request_approve(PDO $pdo, int $requestId, int $reviewerId): array
{
    if ($requestId < 1) {
        throw new RuntimeException('Invalid request.');
    }
    if ($reviewerId < 1) {
        throw new RuntimeException('Reviewer is required.');
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM pos_discount_requests WHERE id = ? AND status = "pending" LIMIT 1 FOR UPDATE');
        $st->execute([$requestId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Discount request not found or already reviewed.');
        }

        $cartRaw = (string) ($row['cart_json'] ?? '');
        $items = json_decode($cartRaw, true);
        if (!is_array($items) || $items === []) {
            throw new RuntimeException('Discount request cart is invalid.');
        }

        $upd = $pdo->prepare(
            'UPDATE pos_discount_requests
             SET reviewed_by = ?, reviewed_at = NOW(), status = "approved"
             WHERE id = ?'
        );
        $upd->execute([$reviewerId, $requestId]);

        $checkoutOpts = [
            'discount_percent' => (int) $row['discount_percent'],
            'discount_request_id' => $requestId,
        ];

        if (pos_discount_cart_is_unified($items)) {
            $result = pos_unified_checkout(
                $pdo,
                ['pin_id' => (int) $row['sold_by_pin_id'], 'user_id' => null],
                $items,
                (string) $row['buyer_name'],
                (string) ($row['buyer_credentials'] ?? ''),
                $checkoutOpts
            );
        } elseif (pos_discount_cart_is_dept($items)) {
            $result = dept_pos_checkout(
                $pdo,
                (int) $row['sold_by_pin_id'],
                $items,
                (string) $row['buyer_name'],
                (string) ($row['buyer_credentials'] ?? ''),
                $checkoutOpts
            );
        } else {
            $result = honey_pos_checkout(
                $pdo,
                (int) $row['sold_by_pin_id'],
                $items,
                (string) $row['buyer_name'],
                (string) ($row['buyer_credentials'] ?? ''),
                $checkoutOpts
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $result;
}

function pos_discount_request_decline(PDO $pdo, int $requestId, int $reviewerId, ?string $note = null): void
{
    if ($requestId < 1) {
        throw new RuntimeException('Invalid request.');
    }
    if ($reviewerId < 1) {
        throw new RuntimeException('Reviewer is required.');
    }
    $note = trim((string) $note);
    $upd = $pdo->prepare(
        'UPDATE pos_discount_requests
         SET status = "declined", reviewed_by = ?, reviewed_at = NOW(), decline_note = ?
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([$reviewerId, $note !== '' ? $note : null, $requestId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Discount request not found or already reviewed.');
    }
}

/** @return array<string,mixed> */
function pos_discount_request_fetch_for_pin(PDO $pdo, int $requestId, int $posPinId): array
{
    $st = $pdo->prepare('SELECT * FROM pos_discount_requests WHERE id = ? AND sold_by_pin_id = ? LIMIT 1');
    $st->execute([$requestId, $posPinId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Discount request not found.');
    }

    $payload = [
        'request_id' => (int) $row['id'],
        'request_no' => (string) $row['request_no'],
        'status' => (string) $row['status'],
        'buyer_name' => (string) $row['buyer_name'],
        'discount_percent' => (int) $row['discount_percent'],
        'subtotal' => (float) $row['subtotal_amount'],
        'discount_amount' => (float) $row['discount_amount'],
        'total' => (float) $row['total_amount'],
        'decline_note' => $row['decline_note'] !== null ? (string) $row['decline_note'] : null,
    ];

    if ((string) $row['status'] === 'approved' && (int) ($row['pos_sale_id'] ?? 0) > 0) {
        $payload['receipt'] = pos_discount_receipt_payload($pdo, (int) $row['pos_sale_id']);
    }

    return $payload;
}

/** @return array<string,mixed> */
function pos_discount_receipt_payload(PDO $pdo, int $saleId): array
{
    $stSale = $pdo->prepare(
        'SELECT id, receipt_no, buyer_name, subtotal_amount, discount_percent, discount_amount, total_amount
         FROM pos_sales
         WHERE id = ?
         LIMIT 1'
    );
    $stSale->execute([$saleId]);
    $sale = $stSale->fetch(PDO::FETCH_ASSOC);
    if (!$sale) {
        throw new RuntimeException('Receipt not found.');
    }

    $stItems = $pdo->prepare(
        'SELECT pri.product_id, pri.qty, pri.unit_price, pri.list_unit_price, pri.line_total, pri.variant, pri.size, p.name
         FROM pos_receipt_items pri
         LEFT JOIN products p ON p.id = pri.product_id
         WHERE pri.sale_id = ?
         ORDER BY pri.id ASC'
    );
    $stItems->execute([$saleId]);
    $items = [];
    foreach ($stItems->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $items[] = [
            'product_id' => (int) $r['product_id'],
            'qty' => (float) $r['qty'],
            'list_unit_price' => (float) ($r['list_unit_price'] ?? $r['unit_price']),
            'unit_price' => (float) $r['unit_price'],
            'line_subtotal' => round((float) ($r['list_unit_price'] ?? $r['unit_price']) * (float) $r['qty'], 2),
            'line_total' => (float) $r['line_total'],
            'name' => (string) ($r['name'] ?? ''),
            'variant' => $r['variant'] !== null ? (string) $r['variant'] : null,
            'size' => $r['size'] !== null ? (string) $r['size'] : null,
        ];
    }

    return [
        'sale_id' => (int) $sale['id'],
        'receipt_no' => (string) $sale['receipt_no'],
        'buyer_name' => (string) $sale['buyer_name'],
        'subtotal' => (float) ($sale['subtotal_amount'] ?? $sale['total_amount']),
        'discount_percent' => $sale['discount_percent'] !== null ? (int) $sale['discount_percent'] : null,
        'discount_amount' => (float) ($sale['discount_amount'] ?? 0),
        'total' => (float) $sale['total_amount'],
        'items' => $items,
    ];
}

/** @return array{rows:list<array<string,mixed>>,pending_count:int} */
function pos_discount_request_list(PDO $pdo, string $status = 'pending', int $limit = 100): array
{
    $allowed = ['pending', 'approved', 'declined', 'all'];
    if (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }
    $limit = max(1, min(200, $limit));

    $where = '';
    $args = [];
    if ($status !== 'all') {
        $where = 'WHERE r.status = ?';
        $args[] = $status;
    }

    $sql = "SELECT r.*, pin.label AS cashier_label, u.name AS reviewer_name
            FROM pos_discount_requests r
            INNER JOIN pos_pins pin ON pin.id = r.sold_by_pin_id
            LEFT JOIN users u ON u.id = r.reviewed_by
            {$where}
            ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.id DESC
            LIMIT {$limit}";
    $st = $pdo->prepare($sql);
    $st->execute($args);

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cart = json_decode((string) $row['cart_json'], true);
        $rows[] = [
            'id' => (int) $row['id'],
            'request_no' => (string) $row['request_no'],
            'status' => (string) $row['status'],
            'cashier_label' => (string) $row['cashier_label'],
            'buyer_name' => (string) $row['buyer_name'],
            'discount_percent' => (int) $row['discount_percent'],
            'subtotal_amount' => (float) $row['subtotal_amount'],
            'discount_amount' => (float) $row['discount_amount'],
            'total_amount' => (float) $row['total_amount'],
            'item_count' => is_array($cart) ? count($cart) : 0,
            'created_at' => (string) $row['created_at'],
            'reviewed_at' => $row['reviewed_at'] !== null ? (string) $row['reviewed_at'] : null,
            'reviewer_name' => $row['reviewer_name'] !== null ? (string) $row['reviewer_name'] : null,
            'decline_note' => $row['decline_note'] !== null ? (string) $row['decline_note'] : null,
            'pos_sale_id' => $row['pos_sale_id'] !== null ? (int) $row['pos_sale_id'] : null,
        ];
    }

    $pendingCount = (int) $pdo->query('SELECT COUNT(*) FROM pos_discount_requests WHERE status = "pending"')->fetchColumn();

    return ['rows' => $rows, 'pending_count' => $pendingCount];
}

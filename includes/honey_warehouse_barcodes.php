<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/sku_barcodes.php';

const HONEY_WAREHOUSE_BARCODE_MAX_QTY = 5000;

function honey_warehouse_barcode_normalize(string $raw): string
{
    $code = strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');
    if ($code === '' || strlen($code) > 32 || !preg_match('/^[A-Z0-9.-]+$/', $code)) {
        throw new RuntimeException('Enter a valid supplier/date barcode.');
    }

    return $code;
}

function honey_warehouse_barcode_date(string $raw): string
{
    $date = trim($raw);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
        throw new RuntimeException('Arrival date must be a valid date.');
    }

    return $date;
}

function honey_warehouse_barcode_quantity(mixed $raw): int
{
    $qty = filter_var($raw, FILTER_VALIDATE_INT);
    if ($qty === false || $qty < 1 || $qty > HONEY_WAREHOUSE_BARCODE_MAX_QTY) {
        throw new RuntimeException('Label quantity must be between 1 and 5000.');
    }

    return (int) $qty;
}

/**
 * @return array<string, mixed>
 */
function honey_warehouse_barcode_request_create(
    PDO $pdo,
    int $userId,
    int $supplierId,
    string $arrivalDate,
    int $quantity
): array {
    $arrivalDate = honey_warehouse_barcode_date($arrivalDate);
    $quantity = honey_warehouse_barcode_quantity($quantity);
    $supplier = supplier_require_honey_active($pdo, $supplierId);
    $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
    $supplierCode = honey_supplier_code($supplierName, $arrivalDate);
    $stock = $pdo->prepare(
        'SELECT COUNT(*) FROM honey_warehouse_rows
         WHERE supplier_id = ? AND supplier_code = ? AND arrival_date = ?'
    );
    $stock->execute([$supplierId, $supplierCode, $arrivalDate]);
    if ((int) $stock->fetchColumn() < 1) {
        throw new RuntimeException('Stock in this supplier and arrival date before requesting its barcode.');
    }

    $st = $pdo->prepare(
        'INSERT INTO honey_warehouse_barcode_requests
            (supplier_id, supplier_name, supplier_code, arrival_date, quantity, requested_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$supplierId, $supplierName, $supplierCode, $arrivalDate, $quantity, $userId]);

    return honey_warehouse_barcode_request_fetch($pdo, (int) $pdo->lastInsertId()) ?? [
        'id' => 0,
        'supplier_id' => $supplierId,
        'supplier_name' => $supplierName,
        'supplier_code' => $supplierCode,
        'arrival_date' => $arrivalDate,
        'quantity' => $quantity,
        'status' => 'pending',
    ];
}

/**
 * @return array<string, mixed>|null
 */
function honey_warehouse_barcode_request_fetch(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS requested_by_name, g.name AS generated_by_name
         FROM honey_warehouse_barcode_requests r
         INNER JOIN users u ON u.id = r.requested_by
         LEFT JOIN users g ON g.id = r.generated_by
         WHERE r.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * @return list<array<string, mixed>>
 */
function honey_warehouse_barcode_request_list(PDO $pdo, ?string $status = null): array
{
    $where = '';
    $params = [];
    if ($status !== null && $status !== '' && $status !== 'all') {
        if (!in_array($status, ['pending', 'printed', 'cancelled'], true)) {
            throw new RuntimeException('Invalid request status.');
        }
        $where = ' WHERE r.status = ?';
        $params[] = $status;
    }
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS requested_by_name, g.name AS generated_by_name
         FROM honey_warehouse_barcode_requests r
         INNER JOIN users u ON u.id = r.requested_by
         LEFT JOIN users g ON g.id = r.generated_by'
        . $where . ' ORDER BY r.id DESC LIMIT 200'
    );
    $st->execute($params);

    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark a request printed and return it. Reprints do not create a new request.
 *
 * @return array<string, mixed>
 */
function honey_warehouse_barcode_request_generate(PDO $pdo, int $userId, int $requestId): array
{
    $request = honey_warehouse_barcode_request_fetch($pdo, $requestId);
    if ($request === null) {
        throw new RuntimeException('Warehouse barcode request not found.');
    }
    if ((string) $request['status'] === 'cancelled') {
        throw new RuntimeException('Cancelled barcode requests cannot be printed.');
    }

    $pdo->prepare(
        'UPDATE honey_warehouse_barcode_requests
         SET status = "printed", generated_by = ?, printed_at = COALESCE(printed_at, NOW())
         WHERE id = ?'
    )->execute([$userId, $requestId]);

    return honey_warehouse_barcode_request_fetch($pdo, $requestId) ?? $request;
}

/**
 * @return list<array<string, mixed>>
 */
function honey_warehouse_barcode_lookup(PDO $pdo, string $rawCode): array
{
    $code = honey_warehouse_barcode_normalize($rawCode);
    $st = $pdo->prepare(
        'SELECT supplier_id, supplier_name, supplier_code, arrival_date, variant,
                SUM(current_qty) AS current_qty, COUNT(*) AS warehouse_row_count
         FROM honey_warehouse_rows
         WHERE supplier_code = ?
         GROUP BY supplier_id, supplier_name, supplier_code, arrival_date, variant
         ORDER BY arrival_date DESC, supplier_name ASC, variant ASC'
    );
    $st->execute([$code]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        throw new RuntimeException('Supplier/date barcode not found in warehouse stock.');
    }

    return array_map(
        static function (array $row): array {
            $row['supplier_id'] = (int) $row['supplier_id'];
            $row['current_qty'] = (float) $row['current_qty'];
            $row['warehouse_row_count'] = (int) $row['warehouse_row_count'];
            return $row;
        },
        $rows
    );
}

/**
 * Scan a supplier/date code, choose a variant, and move gallons to production.
 *
 * Multiple warehouse rows are consumed oldest-first. New scanner transfers share
 * one production row per stock-out date + supplier code + variant.
 *
 * @return array<string, mixed>
 */
function honey_warehouse_barcode_stock_out(
    PDO $pdo,
    int $userId,
    string $rawCode,
    int $supplierId,
    string $arrivalDate,
    string $variant,
    float $quantity
): array {
    $code = honey_warehouse_barcode_normalize($rawCode);
    $arrivalDate = honey_warehouse_barcode_date($arrivalDate);
    $variant = honey_normalize_variant($variant);
    if ($supplierId < 1) {
        throw new RuntimeException('Select a supplier/date candidate first.');
    }
    if ($quantity <= 0) {
        throw new RuntimeException('Stock-out quantity must be greater than zero.');
    }
    $quantity = round($quantity, 2);
    $stockOutDate = date('Y-m-d');
    $dailyKey = $supplierId . '|' . $code . '|' . $arrivalDate . '|' . $variant . '|' . $stockOutDate;

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT *
             FROM honey_warehouse_rows
             WHERE supplier_id = ? AND supplier_code = ? AND arrival_date = ? AND variant = ?
               AND current_qty > 0
             ORDER BY arrival_date ASC, id ASC
             FOR UPDATE'
        );
        $st->execute([$supplierId, $code, $arrivalDate, $variant]);
        $warehouseRows = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($warehouseRows === []) {
            throw new RuntimeException('No available warehouse stock matches that supplier, date, and variant.');
        }

        $remaining = $quantity;
        $sources = [];
        $update = $pdo->prepare(
            'UPDATE honey_warehouse_rows
             SET current_qty = current_qty - ?
             WHERE id = ? AND current_qty >= ?'
        );
        foreach ($warehouseRows as $warehouse) {
            if ($remaining <= 0.000001) {
                break;
            }
            $available = (float) $warehouse['current_qty'];
            $used = min($available, $remaining);
            $used = round($used, 2);
            if ($used <= 0) {
                continue;
            }
            $update->execute([$used, (int) $warehouse['id'], $used]);
            if ($update->rowCount() < 1) {
                throw new RuntimeException('Warehouse quantity changed. Scan again.');
            }
            $remaining = round($remaining - $used, 2);
            $sources[] = ['row' => $warehouse, 'quantity' => $used];
        }
        if ($remaining > 0.000001) {
            throw new RuntimeException(
                'Not enough warehouse stock for ' . $variant . ' (' . round($quantity - $remaining, 2)
                . ' of ' . $quantity . ' gallon/s available).'
            );
        }

        $prod = $pdo->prepare(
            'SELECT * FROM honey_production_rows WHERE daily_group_key = ? LIMIT 1 FOR UPDATE'
        );
        $prod->execute([$dailyKey]);
        $production = $prod->fetch(PDO::FETCH_ASSOC);
        if ($production === false) {
            $first = $sources[0]['row'];
            $ins = $pdo->prepare(
                'INSERT INTO honey_production_rows
                    (warehouse_row_id, supplier_code, arrival_date, stock_out_date, daily_group_key,
                     variant, current_qty, initial_qty, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                (int) $first['id'],
                $code,
                $arrivalDate,
                $stockOutDate,
                $dailyKey,
                $variant,
                $quantity,
                $quantity,
                $userId,
            ]);
            $productionId = (int) $pdo->lastInsertId();
        } else {
            $productionId = (int) $production['id'];
            $updProd = $pdo->prepare(
                'UPDATE honey_production_rows
                 SET current_qty = current_qty + ?, initial_qty = initial_qty + ?
                 WHERE id = ?'
            );
            $updProd->execute([$quantity, $quantity, $productionId]);
        }

        $event = $pdo->prepare(
            'INSERT INTO honey_warehouse_stock_out_events
                (warehouse_row_id, production_row_id, supplier_id, supplier_code, arrival_date,
                 variant, quantity_gallons, stock_out_date, stocked_out_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($sources as $source) {
            $warehouse = $source['row'];
            $event->execute([
                (int) $warehouse['id'],
                $productionId,
                $supplierId,
                $code,
                $arrivalDate,
                $variant,
                $source['quantity'],
                $stockOutDate,
                $userId,
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
        'production_row_id' => $productionId,
        'supplier_id' => $supplierId,
        'supplier_code' => $code,
        'arrival_date' => $arrivalDate,
        'stock_out_date' => $stockOutDate,
        'variant' => $variant,
        'quantity_gallons' => $quantity,
        'source_row_count' => count($sources),
    ];
}

function honey_warehouse_barcode_print_sheet_html(string $code, int $quantity): string
{
    $code = honey_warehouse_barcode_normalize($code);
    $quantity = honey_warehouse_barcode_quantity($quantity);
    $stream = [];
    for ($i = 0; $i < $quantity; $i++) {
        $stream[] = ['slots' => 1, 'html' => sku_barcode_label_cell_html($code)];
    }

    return sku_barcode_render_packed_label_sheet(
        $stream,
        'Honey warehouse supplier barcode ' . $code,
        $quantity
    );
}

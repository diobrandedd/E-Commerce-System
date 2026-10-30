<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';

/**
 * Unified barcode verify — honey bottles + dept pouches/jars.
 * Read-only; safe for any authenticated IMS user.
 *
 * @return array<string, mixed>
 */
function barcode_verify_lookup(PDO $pdo, string $rawCode): array
{
    $code = strtoupper(trim($rawCode));
    if ($code === '' || strlen($code) > 64) {
        throw new RuntimeException('Enter a barcode to verify.');
    }

    require_once __DIR__ . '/sku_barcodes.php';
    require_once __DIR__ . '/dept_raw_freebies.php';
    $freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);
    if ($freebie !== null) {
        $productKey = $freebie['product_key'];
        $grams = rtrim(rtrim(number_format(dept_raw_freebie_grams($pdo, $productKey), 2, '.', ''), '0'), '.');
        $onHand = dept_raw_freebie_on_hand($pdo, $productKey);

        return [
            'found' => true,
            'kind' => 'freebie',
            'lookup_kind' => 'freebie',
            'code' => $freebie['code'],
            'status' => 'freebie',
            'product_label' => dept_raw_freebie_label($productKey) . ' · ' . $grams . ' g · ' . $onHand . ' on hand',
            'supplier_code' => null,
            'batch_no' => null,
            'batch_date' => null,
            'scanned_at' => null,
            'scanned_by_name' => null,
            'unit_status' => null,
            'requires_qty' => false,
            'honey' => null,
            'dept' => null,
        ];
    }

    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        $ser = sku_barcode_serialize($sku);

        return [
            'found' => true,
            'kind' => 'sku',
            'lookup_kind' => 'sku',
            'code' => (string) $sku['code'],
            'status' => 'sku',
            'product_label' => (string) $ser['label'],
            'supplier_code' => null,
            'batch_no' => null,
            'batch_date' => null,
            'scanned_at' => null,
            'scanned_by_name' => null,
            'unit_status' => null,
            'requires_qty' => true,
            'honey' => (string) $sku['kind'] === 'honey' ? [
                'variant' => (string) $sku['variant'],
                'size' => (string) $sku['size'],
                'size_label' => honey_bottle_size_label((string) $sku['size']),
            ] : null,
            'dept' => (string) $sku['kind'] === 'dept' ? [
                'product_key' => (string) $sku['product_key'],
                'unit' => dept_pouch_unit_singular((string) $sku['product_key']),
                'unit_plural' => dept_pouch_unit_singular((string) $sku['product_key']) === 'jar' ? 'jars' : 'pouches',
            ] : null,
        ];
    }

    $honey = barcode_verify_lookup_honey($pdo, $code);
    if ($honey !== null) {
        return $honey;
    }

    $dept = barcode_verify_lookup_dept($pdo, $code);
    if ($dept !== null) {
        return $dept;
    }

    throw new RuntimeException('Unknown barcode.');
}

/**
 * @return array<string, mixed>|null
 */
function barcode_verify_lookup_honey(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT c.code, c.status, c.size, c.scanned_at, c.scanned_by,
                b.variant, b.supplier_code, b.batch_no, b.batch_date,
                u.name AS scanned_by_name,
                un.status AS unit_status
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN users u ON u.id = c.scanned_by
         LEFT JOIN honey_bottle_units un ON un.barcode_id = c.id
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    return [
        'found' => true,
        'kind' => 'honey',
        'code' => (string) $row['code'],
        'status' => (string) $row['status'],
        'product_label' => 'Honey',
        'supplier_code' => (string) $row['supplier_code'],
        'batch_no' => (string) $row['batch_no'],
        'batch_date' => (string) $row['batch_date'],
        'scanned_at' => $row['scanned_at'] !== null ? (string) $row['scanned_at'] : null,
        'scanned_by_name' => $row['scanned_by_name'] !== null ? (string) $row['scanned_by_name'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        'honey' => [
            'variant' => (string) $row['variant'],
            'size' => (string) $row['size'],
            'size_label' => honey_bottle_size_label((string) $row['size']),
        ],
        'dept' => null,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function barcode_verify_lookup_dept(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT c.code, c.status, c.scanned_at, c.scanned_by,
                b.product_key, b.batch_no, b.batch_date,
                u.name AS scanned_by_name,
                un.status AS unit_status
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN users u ON u.id = c.scanned_by
         LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    $productKey = (string) $row['product_key'];
    $productLabel = dept_pouch_product_label($productKey);
    $unit = dept_pouch_unit_singular($productKey);

    return [
        'found' => true,
        'kind' => 'dept_pouch',
        'code' => (string) $row['code'],
        'status' => (string) $row['status'],
        'product_label' => $productLabel,
        // Dept stickers use batch_no as the human batch / supplier label.
        'supplier_code' => (string) $row['batch_no'],
        'batch_no' => (string) $row['batch_no'],
        'batch_date' => (string) $row['batch_date'],
        'scanned_at' => $row['scanned_at'] !== null ? (string) $row['scanned_at'] : null,
        'scanned_by_name' => $row['scanned_by_name'] !== null ? (string) $row['scanned_by_name'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        'honey' => null,
        'dept' => [
            'product_key' => $productKey,
            'unit' => $unit,
            'unit_plural' => $unit === 'jar' ? 'jars' : 'pouches',
        ],
    ];
}

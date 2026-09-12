<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/rts_schema.php';

/**
 * Permanent SKU barcodes (one code per product). Unique 6-char stickers remain
 * as a leftover path until those units are consumed.
 */

const SKU_BARCODE_CODE_MAX = 32;
const SKU_BARCODE_CODE_MIN = 1;

/**
 * @return list<array{kind:string,variant:string,size:string,product_key:string,label:string}>
 */
function sku_barcode_catalog_slots(): array
{
    $slots = [];
    foreach (['light', 'amber', 'dark'] as $variant) {
        foreach (HONEY_SIZES as $size) {
            $slots[] = [
                'kind' => 'honey',
                'variant' => $variant,
                'size' => $size,
                'product_key' => '',
                'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),
            ];
        }
    }
    foreach (DEPT_PRODUCT_KEYS as $key) {
        $def = dept_product_by_key($key);
        $slots[] = [
            'kind' => 'dept',
            'variant' => '',
            'size' => '',
            'product_key' => $key,
            'label' => (string) ($def['label'] ?? $key),
        ];
    }

    return $slots;
}

/**
 * Canonical hex SKU codes (2026-09 color format). Settings + new print jobs use these.
 *
 * @return array<string, string> identity key (kind|variant|size|product_key) => code
 */
function sku_barcode_default_codes(): array
{
    return [
        'honey|light|liter|' => 'F60000',
        'honey|amber|liter|' => 'FFBF00',
        'honey|dark|liter|' => '060000',
        'honey|light|350ml|' => 'F54927',
        'honey|amber|350ml|' => '473600',
        'honey|dark|350ml|' => '023020',
        'honey|light|250ml|' => 'FFA259',
        'honey|amber|250ml|' => 'C62828',
        'honey|dark|250ml|' => '1B1212',
        'honey|light|gallon|' => 'F08B51',
        'honey|amber|gallon|' => '621F04',
        'honey|dark|gallon|' => '1F150C',
        'dept|||pt' => '00FF00',
        'dept|||brc' => '7F00FF',
        'dept|||jar_cacao' => '964B00',
        'dept|||honey_comb' => 'E37434',
    ];
}

/**
 * Pre-2026-09 text SKU codes still on printed stickers — resolve to the same catalog row.
 *
 * @return array<string, array{kind:string,variant:string,size:string,product_key:string}>
 */
function sku_barcode_legacy_aliases(): array
{
    return [
        '1literl' => ['kind' => 'honey', 'variant' => 'light', 'size' => 'liter', 'product_key' => ''],
        '1litera' => ['kind' => 'honey', 'variant' => 'amber', 'size' => 'liter', 'product_key' => ''],
        '1literd' => ['kind' => 'honey', 'variant' => 'dark', 'size' => 'liter', 'product_key' => ''],
        '350mll' => ['kind' => 'honey', 'variant' => 'light', 'size' => '350ml', 'product_key' => ''],
        '350mla' => ['kind' => 'honey', 'variant' => 'amber', 'size' => '350ml', 'product_key' => ''],
        '350mld' => ['kind' => 'honey', 'variant' => 'dark', 'size' => '350ml', 'product_key' => ''],
        '250mll' => ['kind' => 'honey', 'variant' => 'light', 'size' => '250ml', 'product_key' => ''],
        '250mla' => ['kind' => 'honey', 'variant' => 'amber', 'size' => '250ml', 'product_key' => ''],
        '250mld' => ['kind' => 'honey', 'variant' => 'dark', 'size' => '250ml', 'product_key' => ''],
        '1gallonl' => ['kind' => 'honey', 'variant' => 'light', 'size' => 'gallon', 'product_key' => ''],
        '1gallona' => ['kind' => 'honey', 'variant' => 'amber', 'size' => 'gallon', 'product_key' => ''],
        '1gallond' => ['kind' => 'honey', 'variant' => 'dark', 'size' => 'gallon', 'product_key' => ''],
        '1pouchpt' => ['kind' => 'dept', 'variant' => '', 'size' => '', 'product_key' => 'pt'],
        '1pouchbrc' => ['kind' => 'dept', 'variant' => '', 'size' => '', 'product_key' => 'brc'],
        '1jarcacao' => ['kind' => 'dept', 'variant' => '', 'size' => '', 'product_key' => 'jar_cacao'],
        '1jarhoney' => ['kind' => 'dept', 'variant' => '', 'size' => '', 'product_key' => 'honey_comb'],
    ];
}

function sku_barcode_identity_key(string $kind, string $variant, string $size, string $productKey): string
{
    return $kind . '|' . $variant . '|' . $size . '|' . $productKey;
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_by_identity(
    PDO $pdo,
    string $kind,
    string $variant,
    string $size,
    string $productKey
): ?array {
    if ($kind === 'honey') {
        return sku_barcode_fetch_honey($pdo, $variant, $size);
    }

    return sku_barcode_fetch_dept($pdo, $productKey);
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_legacy_alias(PDO $pdo, string $rawCode): ?array
{
    $key = strtolower(sku_barcode_normalize_code($rawCode));
    if ($key === '') {
        return null;
    }
    $aliases = sku_barcode_legacy_aliases();
    if (!isset($aliases[$key])) {
        return null;
    }
    $slot = $aliases[$key];

    return sku_barcode_fetch_by_identity(
        $pdo,
        $slot['kind'],
        $slot['variant'],
        $slot['size'],
        $slot['product_key']
    );
}

function sku_barcode_normalize_code(string $raw): string
{
    return preg_replace('/\s+/', '', trim($raw)) ?? '';
}

function sku_barcode_assert_code(string $code): string
{
    $code = sku_barcode_normalize_code($code);
    $len = strlen($code);
    if ($len < SKU_BARCODE_CODE_MIN || $len > SKU_BARCODE_CODE_MAX) {
        throw new RuntimeException('Barcode must be 1–32 characters (no spaces).');
    }
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $code)) {
        throw new RuntimeException('Barcode may only use letters, digits, dots, or dashes.');
    }

    return $code;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function sku_barcode_serialize(array $row, ?float $sellingPriceHint = null): array
{
    $kind = (string) $row['kind'];
    $variant = (string) ($row['variant'] ?? '');
    $size = (string) ($row['size'] ?? '');
    $productKey = (string) ($row['product_key'] ?? '');
    if ($kind === 'honey') {
        $label = ucfirst($variant) . ' · ' . honey_bottle_size_label($size);
    } else {
        $def = dept_product_by_key($productKey);
        $label = (string) ($def['label'] ?? $productKey);
    }

    return [
        'id' => (int) $row['id'],
        'kind' => $kind,
        'variant' => $kind === 'honey' ? $variant : null,
        'size' => $kind === 'honey' ? $size : null,
        'size_label' => $kind === 'honey' ? honey_bottle_size_label($size) : null,
        'product_key' => $kind === 'dept' ? $productKey : null,
        'label' => $label,
        'code' => (string) $row['code'],
        'selling_price_hint' => $sellingPriceHint,
        'updated_at' => isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        'updated_by_name' => isset($row['updated_by_name']) ? (string) $row['updated_by_name'] : null,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_by_id(PDO $pdo, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, u.name AS updated_by_name
         FROM product_sku_barcodes s
         LEFT JOIN users u ON u.id = s.updated_by
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_by_code(PDO $pdo, string $rawCode): ?array
{
    $code = sku_barcode_normalize_code($rawCode);
    if ($code === '') {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, u.name AS updated_by_name
         FROM product_sku_barcodes s
         LEFT JOIN users u ON u.id = s.updated_by
         WHERE LOWER(s.code) = LOWER(?) LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) {
        return $row;
    }

    return sku_barcode_fetch_legacy_alias($pdo, $code);
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_honey(PDO $pdo, string $variant, string $size): ?array
{
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, u.name AS updated_by_name
         FROM product_sku_barcodes s
         LEFT JOIN users u ON u.id = s.updated_by
         WHERE s.kind = "honey" AND s.variant = ? AND s.size = ? LIMIT 1'
    );
    $st->execute([$variant, $size]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * @return array<string, mixed>|null
 */
function sku_barcode_fetch_dept(PDO $pdo, string $productKey): ?array
{
    $productKey = strtolower(trim($productKey));
    if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, u.name AS updated_by_name
         FROM product_sku_barcodes s
         LEFT JOIN users u ON u.id = s.updated_by
         WHERE s.kind = "dept" AND s.product_key = ? LIMIT 1'
    );
    $st->execute([$productKey]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function sku_barcode_selling_price_hint(PDO $pdo, string $kind, string $variant, string $size, string $productKey): ?float
{
    if ($kind === 'honey') {
        require_once __DIR__ . '/honey_pos.php';
        $deptId = honey_pos_resolve_honey_dept_id($pdo);
        if ($deptId < 1) {
            return null;
        }
        $st = $pdo->prepare(
            'SELECT selling_price FROM products
             WHERE dept_id = ? AND LOWER(variant) = ? AND size = ?
             ORDER BY id ASC LIMIT 1'
        );
        $st->execute([$deptId, strtolower($variant), $size]);
        $price = $st->fetchColumn();
        if ($price === false) {
            return null;
        }

        return round((float) $price, 2);
    }

    $st = $pdo->prepare(
        'SELECT selling_price FROM products
         WHERE dept_product_key = ?
         ORDER BY id ASC LIMIT 1'
    );
    $st->execute([$productKey]);
    $price = $st->fetchColumn();
    if ($price === false) {
        return null;
    }

    return round((float) $price, 2);
}

/**
 * Product rows in Product Setup that match a barcode catalog slot.
 *
 * @return list<int>
 */
function sku_barcode_product_ids_for_slot(PDO $pdo, string $kind, string $variant, string $size, string $productKey): array
{
    if ($kind === 'honey') {
        require_once __DIR__ . '/honey_pos.php';
        $deptId = honey_pos_resolve_honey_dept_id($pdo);
        if ($deptId < 1) {
            return [];
        }
        $st = $pdo->prepare(
            'SELECT id FROM products
             WHERE dept_id = ? AND LOWER(variant) = ? AND size = ?
             ORDER BY id ASC'
        );
        $st->execute([$deptId, strtolower($variant), $size]);

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    $productKey = strtolower(trim($productKey));
    if ($productKey === '') {
        return [];
    }
    $st = $pdo->prepare(
        'SELECT id FROM products WHERE dept_product_key = ? ORDER BY id ASC'
    );
    $st->execute([$productKey]);

    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Update selling price on Product Setup rows for this slot (POS / costing).
 */
function sku_barcode_update_selling_price(
    PDO $pdo,
    string $kind,
    ?string $variant,
    ?string $size,
    ?string $productKey,
    float $sellingPrice
): float
{
    if (!in_array($kind, ['honey', 'dept'], true)) {
        throw new RuntimeException('Invalid SKU kind.');
    }
    if ($kind === 'honey') {
        $variant = honey_normalize_variant((string) $variant);
        $size = trim((string) $size);
        if (!in_array($size, HONEY_SIZES, true)) {
            throw new RuntimeException('Invalid honey size.');
        }
        $productKey = '';
        $label = ucfirst($variant) . ' · ' . honey_bottle_size_label($size);
    } else {
        $productKey = strtolower(trim((string) $productKey));
        if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
            throw new RuntimeException('Invalid dept product.');
        }
        $variant = '';
        $size = '';
        $def = dept_product_by_key($productKey);
        $label = (string) ($def['label'] ?? $productKey);
    }

    if ($sellingPrice < 0) {
        throw new RuntimeException('Selling price must be 0 or greater.');
    }
    $sellingPrice = round($sellingPrice, 2);

    $ids = sku_barcode_product_ids_for_slot($pdo, $kind, $variant, $size, $productKey);
    if ($ids === []) {
        throw new RuntimeException(
            'No product in Product Setup for ' . $label . '. Create it in Costing / Product Setup first.'
        );
    }

    $upd = $pdo->prepare('UPDATE products SET selling_price = ? WHERE id = ?');
    foreach ($ids as $id) {
        $upd->execute([$sellingPrice, $id]);
    }

    return $sellingPrice;
}

/**
 * Full catalog: every slot, with code if set.
 *
 * @return list<array<string, mixed>>
 */
function sku_barcode_list_settings(PDO $pdo): array
{
    $byIdentity = [];
    $st = $pdo->query(
        'SELECT s.*, u.name AS updated_by_name
         FROM product_sku_barcodes s
         LEFT JOIN users u ON u.id = s.updated_by'
    );
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $key = $row['kind'] . '|' . $row['variant'] . '|' . $row['size'] . '|' . $row['product_key'];
        $byIdentity[$key] = $row;
    }

    $out = [];
    foreach (sku_barcode_catalog_slots() as $slot) {
        $key = $slot['kind'] . '|' . $slot['variant'] . '|' . $slot['size'] . '|' . $slot['product_key'];
        $hint = sku_barcode_selling_price_hint(
            $pdo,
            $slot['kind'],
            $slot['variant'],
            $slot['size'],
            $slot['product_key']
        );
        $defaults = sku_barcode_default_codes();
        if (isset($byIdentity[$key])) {
            $out[] = sku_barcode_serialize($byIdentity[$key], $hint);
        } else {
            $out[] = [
                'id' => null,
                'kind' => $slot['kind'],
                'variant' => $slot['kind'] === 'honey' ? $slot['variant'] : null,
                'size' => $slot['kind'] === 'honey' ? $slot['size'] : null,
                'size_label' => $slot['kind'] === 'honey' ? honey_bottle_size_label($slot['size']) : null,
                'product_key' => $slot['kind'] === 'dept' ? $slot['product_key'] : null,
                'label' => $slot['label'],
                'code' => $defaults[$key] ?? '',
                'selling_price_hint' => $hint,
                'updated_at' => null,
                'updated_by_name' => null,
            ];
        }
    }

    return $out;
}

/**
 * Upsert one SKU code. Empty code deletes the row if present.
 *
 * @return array<string, mixed>
 */
function sku_barcode_save(
    PDO $pdo,
    int $userId,
    string $kind,
    ?string $variant,
    ?string $size,
    ?string $productKey,
    string $rawCode
): array {
    if (!in_array($kind, ['honey', 'dept'], true)) {
        throw new RuntimeException('Invalid SKU kind.');
    }
    if ($kind === 'honey') {
        $variant = honey_normalize_variant((string) $variant);
        $size = trim((string) $size);
        if (!in_array($size, HONEY_SIZES, true)) {
            throw new RuntimeException('Invalid honey size.');
        }
        $productKey = '';
    } else {
        $productKey = strtolower(trim((string) $productKey));
        if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
            throw new RuntimeException('Invalid dept product.');
        }
        $variant = '';
        $size = '';
    }

    $codeTrim = trim($rawCode);
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $existing = $kind === 'honey'
            ? sku_barcode_fetch_honey($pdo, $variant, $size)
            : sku_barcode_fetch_dept($pdo, $productKey);

        if ($codeTrim === '') {
            if ($existing !== null) {
                $pdo->prepare('DELETE FROM product_sku_barcodes WHERE id = ?')->execute([(int) $existing['id']]);
            }
            if ($owns) {
                $pdo->commit();
            }

            return [
                'id' => null,
                'kind' => $kind,
                'variant' => $kind === 'honey' ? $variant : null,
                'size' => $kind === 'honey' ? $size : null,
                'size_label' => $kind === 'honey' ? honey_bottle_size_label($size) : null,
                'product_key' => $kind === 'dept' ? $productKey : null,
                'label' => $kind === 'honey'
                    ? (ucfirst($variant) . ' · ' . honey_bottle_size_label($size))
                    : (string) (dept_product_by_key($productKey)['label'] ?? $productKey),
                'code' => '',
                'selling_price_hint' => sku_barcode_selling_price_hint($pdo, $kind, $variant, $size, $productKey),
                'updated_at' => null,
                'updated_by_name' => null,
            ];
        }

        $code = sku_barcode_assert_code($codeTrim);
        $dup = $pdo->prepare(
            'SELECT id, kind, variant, size, product_key FROM product_sku_barcodes
             WHERE LOWER(code) = LOWER(?) LIMIT 1'
        );
        $dup->execute([$code]);
        $dupRow = $dup->fetch(PDO::FETCH_ASSOC);
        if ($dupRow !== false) {
            $same = (int) $dupRow['id'] === (int) ($existing['id'] ?? 0);
            if (!$same) {
                throw new RuntimeException(
                    'Code "' . $code . '" is already used by another SKU. Codes must be globally unique.'
                );
            }
        }

        // Collision with leftover unique stickers would make lookup ambiguous.
        require_once __DIR__ . '/honey_bottle_barcodes.php';
        require_once __DIR__ . '/dept_pouch_barcodes.php';
        $honeyHit = $pdo->prepare('SELECT 1 FROM honey_bottle_barcodes WHERE code = ? LIMIT 1');
        $honeyHit->execute([$code]);
        if ($honeyHit->fetchColumn()) {
            throw new RuntimeException('Code "' . $code . '" matches an existing unique bottle sticker. Pick another.');
        }
        $deptHit = $pdo->prepare('SELECT 1 FROM dept_pouch_barcodes WHERE code = ? LIMIT 1');
        $deptHit->execute([$code]);
        if ($deptHit->fetchColumn()) {
            throw new RuntimeException('Code "' . $code . '" matches an existing unique pouch/jar sticker. Pick another.');
        }

        if ($existing !== null) {
            $pdo->prepare(
                'UPDATE product_sku_barcodes SET code = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$code, $userId, (int) $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $pdo->prepare(
                'INSERT INTO product_sku_barcodes (kind, variant, size, product_key, code, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$kind, $variant, $size, $productKey, $code, $userId]);
            $id = (int) $pdo->lastInsertId();
        }
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $row = sku_barcode_fetch_by_id($pdo, $id);
    if ($row === null) {
        throw new RuntimeException('Could not save SKU barcode.');
    }

    return sku_barcode_serialize(
        $row,
        sku_barcode_selling_price_hint($pdo, $kind, $variant, $size, $productKey)
    );
}

/**
 * Save many SKU codes in one transaction (Barcode Settings bulk save).
 *
 * @param list<array{kind:string,variant?:string,size?:string,product_key?:string,code:string,selling_price?:mixed}> $items
 * @return list<array<string, mixed>>
 */
function sku_barcode_save_bulk(PDO $pdo, int $userId, array $items): array
{
    if ($items === []) {
        return [];
    }

    $seen = [];
    foreach ($items as $item) {
        $codeTrim = trim((string) ($item['code'] ?? ''));
        if ($codeTrim === '') {
            continue;
        }
        $code = sku_barcode_assert_code($codeTrim);
        $lc = strtolower($code);
        if (isset($seen[$lc])) {
            throw new RuntimeException(
                'Code "' . $code . '" is used more than once in this save. Each product needs a unique code.'
            );
        }
        $seen[$lc] = true;
    }

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $out = [];
        foreach ($items as $item) {
            $kind = (string) ($item['kind'] ?? '');
            $variant = isset($item['variant']) ? (string) $item['variant'] : null;
            $size = isset($item['size']) ? (string) $item['size'] : null;
            $productKey = isset($item['product_key']) ? (string) $item['product_key'] : null;

            if (array_key_exists('selling_price', $item)) {
                $priceRaw = trim((string) $item['selling_price']);
                if ($priceRaw !== '' && is_numeric($priceRaw)) {
                    sku_barcode_update_selling_price(
                        $pdo,
                        $kind,
                        $variant,
                        $size,
                        $productKey,
                        (float) $priceRaw
                    );
                }
            }

            $out[] = sku_barcode_save(
                $pdo,
                $userId,
                $kind,
                $variant,
                $size,
                $productKey,
                (string) ($item['code'] ?? '')
            );
        }
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $out;
}

/**
 * Shared scan lookup: SKU first, then leftover unique unit codes.
 *
 * @return array<string, mixed>
 */
function sku_barcode_lookup(PDO $pdo, string $rawCode): array
{
    $code = sku_barcode_normalize_code($rawCode);
    if ($code === '') {
        throw new RuntimeException('Scan a barcode.');
    }

    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        $ser = sku_barcode_serialize($sku);
        $ser['lookup_kind'] = 'sku';
        $ser['requires_qty'] = true;
        $ser['code'] = (string) $sku['code'];

        return $ser;
    }

    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/dept_pouch_barcodes.php';

    $honeyRow = honey_bottle_fetch_code_row($pdo, $code);
    if ($honeyRow !== null) {
        $lookup = honey_bottle_serialize_lookup($honeyRow);
        $lookup['lookup_kind'] = 'unique_honey';
        $lookup['requires_qty'] = false;
        $lookup['kind'] = 'honey';
        $lookup['label'] = ucfirst((string) $lookup['variant']) . ' · ' . (string) $lookup['size_label'];

        return $lookup;
    }

    try {
        // dept fetch without requiring in_stock — verify-style
        $st = $pdo->prepare(
            'SELECT c.*, b.batch_no, b.product_key, b.batch_date,
                    un.id AS unit_id, un.status AS unit_status, un.pool AS unit_pool
             FROM dept_pouch_barcodes c
             INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
             WHERE c.code = ? LIMIT 1'
        );
        $st->execute([$code]);
        $dept = $st->fetch(PDO::FETCH_ASSOC);
        if ($dept !== false) {
            $productKey = (string) ($dept['product_key'] ?? 'pt');

            return [
                'lookup_kind' => 'unique_dept',
                'requires_qty' => false,
                'kind' => 'dept',
                'product_key' => $productKey,
                'product_label' => dept_pouch_product_label($productKey),
                'label' => dept_pouch_product_label($productKey),
                'code' => (string) $dept['code'],
                'barcode_id' => (int) $dept['id'],
                'unit_id' => $dept['unit_id'] !== null ? (int) $dept['unit_id'] : null,
                'unit_status' => $dept['unit_status'] !== null ? (string) $dept['unit_status'] : null,
                'batch_no' => (string) ($dept['batch_no'] ?? ''),
                'status' => (string) $dept['status'],
            ];
        }
    } catch (Throwable) {
        // fall through
    }

    throw new RuntimeException('Unknown barcode.');
}

/**
 * Credit SKU stock-in qty against open label requests (FIFO) and log events.
 *
 * @return array{primary_request_id: ?int, credited: int}
 */
function sku_barcode_credit_honey_requests(
    PDO $pdo,
    int $userId,
    int $skuId,
    string $variant,
    string $size,
    int $qty,
    int $packagingRowId
): array {
    $variant = honey_normalize_variant($variant);
    $remaining = $qty;
    $primaryRequestId = null;
    $insEvent = $pdo->prepare(
        'INSERT INTO sku_barcode_stock_events
            (kind, sku_id, qty, packaging_row_id, honey_request_id, scanned_by)
         VALUES ("honey", ?, ?, ?, ?, ?)'
    );
    $updReq = $pdo->prepare(
        'UPDATE honey_bottle_requests SET sku_scanned_qty = sku_scanned_qty + ? WHERE id = ?'
    );
    $st = $pdo->prepare(
        'SELECT id, quantity, COALESCE(sku_scanned_qty, 0) AS sku_scanned_qty
         FROM honey_bottle_requests
         WHERE variant = ? AND size = ? AND status IN ("barcodes_generated", "in_progress")
         ORDER BY id ASC FOR UPDATE'
    );
    $st->execute([$variant, $size]);
    while ($remaining > 0) {
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            break;
        }
        $reqId = (int) $row['id'];
        $legacySt = $pdo->prepare(
            'SELECT COUNT(*) FROM honey_barcode_batches b
             INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
             WHERE b.request_id = ? AND c.status = "scanned"'
        );
        $legacySt->execute([$reqId]);
        $legacyScanned = (int) $legacySt->fetchColumn();
        $open = (int) $row['quantity'] - (int) $row['sku_scanned_qty'] - $legacyScanned;
        if ($open <= 0) {
            continue;
        }
        $take = min($remaining, $open);
        $updReq->execute([$take, $reqId]);
        $insEvent->execute([$skuId, $take, $packagingRowId > 0 ? $packagingRowId : null, $reqId, $userId]);
        sku_barcode_refresh_honey_request_status($pdo, $reqId);
        if ($primaryRequestId === null) {
            $primaryRequestId = $reqId;
        }
        $remaining -= $take;
    }
    if ($remaining > 0) {
        $insEvent->execute([$skuId, $remaining, $packagingRowId > 0 ? $packagingRowId : null, null, $userId]);
    }

    return ['primary_request_id' => $primaryRequestId, 'credited' => $qty];
}

function sku_barcode_refresh_honey_request_status(PDO $pdo, int $requestId): void
{
    $st = $pdo->prepare(
        'SELECT quantity, COALESCE(sku_scanned_qty, 0) AS sku_scanned_qty FROM honey_bottle_requests WHERE id = ? LIMIT 1'
    );
    $st->execute([$requestId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return;
    }
    $legacySt = $pdo->prepare(
        'SELECT COUNT(*) FROM honey_barcode_batches b
         INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
         WHERE b.request_id = ? AND c.status = "scanned"'
    );
    $legacySt->execute([$requestId]);
    $legacyScanned = (int) $legacySt->fetchColumn();
    $scanned = (int) $row['sku_scanned_qty'] + $legacyScanned;
    $target = (int) $row['quantity'];
    $status = $scanned >= $target && $target > 0 ? 'completed' : 'in_progress';
    $pdo->prepare('UPDATE honey_bottle_requests SET status = ? WHERE id = ? AND status <> "cancelled"')
        ->execute([$status, $requestId]);
}

/**
 * @return array{primary_request_id: ?int, credited: int}
 */
function sku_barcode_credit_dept_requests(
    PDO $pdo,
    int $userId,
    int $skuId,
    string $productKey,
    int $qty
): array {
    $productKey = strtolower(trim($productKey));
    $remaining = $qty;
    $primaryRequestId = null;
    $insEvent = $pdo->prepare(
        'INSERT INTO sku_barcode_stock_events
            (kind, sku_id, qty, dept_request_id, scanned_by)
         VALUES ("dept", ?, ?, ?, ?)'
    );
    $updReq = $pdo->prepare(
        'UPDATE dept_pouch_requests SET sku_scanned_qty = sku_scanned_qty + ? WHERE id = ?'
    );
    $st = $pdo->prepare(
        'SELECT id, quantity, COALESCE(sku_scanned_qty, 0) AS sku_scanned_qty
         FROM dept_pouch_requests
         WHERE product_key = ? AND status IN ("barcodes_generated", "in_progress")
         ORDER BY id ASC FOR UPDATE'
    );
    $st->execute([$productKey]);
    while ($remaining > 0) {
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            break;
        }
        $reqId = (int) $row['id'];
        $legacySt = $pdo->prepare(
            'SELECT COUNT(*) FROM dept_pouch_barcode_batches b
             INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
             WHERE b.request_id = ? AND c.status = "scanned"'
        );
        $legacySt->execute([$reqId]);
        $legacyScanned = (int) $legacySt->fetchColumn();
        $open = (int) $row['quantity'] - (int) $row['sku_scanned_qty'] - $legacyScanned;
        if ($open <= 0) {
            continue;
        }
        $take = min($remaining, $open);
        $updReq->execute([$take, $reqId]);
        $insEvent->execute([$skuId, $take, $reqId, $userId]);
        sku_barcode_refresh_dept_request_status($pdo, $reqId);
        if ($primaryRequestId === null) {
            $primaryRequestId = $reqId;
        }
        $remaining -= $take;
    }
    if ($remaining > 0) {
        $insEvent->execute([$skuId, $remaining, null, $userId]);
    }

    return ['primary_request_id' => $primaryRequestId, 'credited' => $qty];
}

function sku_barcode_refresh_dept_request_status(PDO $pdo, int $requestId): void
{
    $st = $pdo->prepare(
        'SELECT quantity, COALESCE(sku_scanned_qty, 0) AS sku_scanned_qty FROM dept_pouch_requests WHERE id = ? LIMIT 1'
    );
    $st->execute([$requestId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return;
    }
    $legacySt = $pdo->prepare(
        'SELECT COUNT(*) FROM dept_pouch_barcode_batches b
         INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
         WHERE b.request_id = ? AND c.status = "scanned"'
    );
    $legacySt->execute([$requestId]);
    $legacyScanned = (int) $legacySt->fetchColumn();
    $scanned = (int) $row['sku_scanned_qty'] + $legacyScanned;
    $target = (int) $row['quantity'];
    $status = $scanned >= $target && $target > 0 ? 'completed' : 'in_progress';
    $pdo->prepare('UPDATE dept_pouch_requests SET status = ? WHERE id = ? AND status <> "cancelled"')
        ->execute([$status, $requestId]);
}

/**
 * @return list<array<string, mixed>>
 */
function sku_barcode_print_job_list(PDO $pdo, ?string $kind = null, int $limit = 40): array
{
    $limit = max(1, min(100, $limit));
    $where = '1=1';
    $params = [];
    if ($kind === 'honey') {
        $where .= ' AND j.honey_request_id IS NOT NULL';
    } elseif ($kind === 'dept') {
        $where .= ' AND j.dept_request_id IS NOT NULL';
    }
    $st = $pdo->prepare(
        "SELECT j.*, s.code AS sku_code, s.kind AS sku_kind, s.variant, s.size, s.product_key,
                u.name AS printed_by_name,
                hr.variant AS honey_variant, hr.size AS honey_size, hr.quantity AS honey_qty,
                dr.product_key AS dept_product_key, dr.quantity AS dept_qty
         FROM sku_barcode_print_jobs j
         INNER JOIN product_sku_barcodes s ON s.id = j.sku_id
         LEFT JOIN users u ON u.id = j.printed_by
         LEFT JOIN honey_bottle_requests hr ON hr.id = j.honey_request_id
         LEFT JOIN dept_pouch_requests dr ON dr.id = j.dept_request_id
         WHERE {$where}
         ORDER BY j.id DESC
         LIMIT {$limit}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $label = '';
        if ((string) $row['sku_kind'] === 'honey') {
            $label = ucfirst((string) $row['variant']) . ' · ' . honey_bottle_size_label((string) $row['size']);
        } else {
            $label = dept_pouch_product_label((string) $row['product_key']);
        }
        $rows[] = [
            'id' => (int) $row['id'],
            'sku_id' => (int) $row['sku_id'],
            'sku_code' => (string) $row['sku_code'],
            'product_key' => (string) $row['product_key'],
            'label' => $label,
            'quantity' => (int) $row['quantity'],
            'request_kind' => (string) $row['request_kind'],
            'honey_request_id' => $row['honey_request_id'] !== null ? (int) $row['honey_request_id'] : null,
            'dept_request_id' => $row['dept_request_id'] !== null ? (int) $row['dept_request_id'] : null,
            'printed_by_name' => (string) ($row['printed_by_name'] ?? ''),
            'printed_at' => (string) $row['printed_at'],
            'print_url' => '/rts/api/v1/sku_barcodes.php?action=print_sheet&sku_id='
                . (int) $row['sku_id'] . '&qty=' . (int) $row['quantity'],
        ];
    }

    return $rows;
}

/**
 * Credit packaging aggregate by SKU (no unit rows).
 *
 * @return array<string, mixed>
 */
function sku_barcode_honey_stock_in(
    PDO $pdo,
    int $userId,
    string $variant,
    string $size,
    int $qty,
    int $packagingRowId
): array {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid size.');
    }
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    if ($packagingRowId < 1) {
        throw new RuntimeException('Pick a packaging batch before stocking in.');
    }
    $sourcePk = honey_fetch_packaging($pdo, $packagingRowId);
    if ($sourcePk === null) {
        throw new RuntimeException('Packaging row not found.');
    }
    if (honey_normalize_variant((string) $sourcePk['variant']) !== $variant) {
        throw new RuntimeException('SKU variant does not match the packaging batch.');
    }
    // Same rule as unique stickers: picked batch identity + today's bottling date.
    $todayPk = honey_ensure_packaging_row_for_stock_in(
        $pdo,
        $userId,
        (string) $sourcePk['batch_no'],
        $variant,
        (string) $sourcePk['supplier_code'],
        date('Y-m-d'),
        isset($sourcePk['production_row_id']) ? (int) $sourcePk['production_row_id'] : null
    );
    $packagingRowId = (int) $todayPk['id'];
    honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, (float) $qty);

    $sku = sku_barcode_fetch_honey($pdo, $variant, $size);
    $credit = ['primary_request_id' => null, 'credited' => $qty];
    if ($sku !== null) {
        $credit = sku_barcode_credit_honey_requests(
            $pdo,
            $userId,
            (int) $sku['id'],
            $variant,
            $size,
            $qty,
            $packagingRowId
        );
    }

    return [
        'kind' => 'honey',
        'variant' => $variant,
        'size' => $size,
        'size_label' => honey_bottle_size_label($size),
        'qty' => $qty,
        'packaging_row_id' => $packagingRowId,
        'batch_no' => (string) $todayPk['batch_no'],
        'bottling_date' => (string) $todayPk['bottling_date'],
        'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),
        'primary_request_id' => $credit['primary_request_id'],
    ];
}

/**
 * @return array<string, mixed>
 */
function sku_barcode_dept_stock_in(PDO $pdo, int $userId, string $productKey, int $qty): array
{
    $productKey = strtolower(trim($productKey));
    if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
        throw new RuntimeException('Invalid product.');
    }
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    $fin = dept_inventory_ensure_row($pdo, $userId, $productKey);
    $label = dept_pouch_product_label($productKey);
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$qty, $qty, $label, (int) $fin['id']]);

    $bagPool = null;
    if ($productKey === 'pt') {
        require_once __DIR__ . '/dept_pt_settings.php';
        if (dept_pt_setting_bag_pool_deduct_enabled($pdo)) {
            $bags = dept_pt_setting_bags_per_pouch($pdo) * $qty;
            $deduct = dept_raw_deduct_bags_allow_negative($pdo, $userId, 'pt', $bags, 'sku_pouch_scan');
            $bagPool = [
                'bags_deducted' => $deduct['bags_deducted'],
                'bags_on_hand' => $deduct['bags_on_hand'],
                'grams_deducted' => round(dept_pt_setting_grams_per_pouch($pdo) * $qty, 2),
                'grams_remaining' => round($deduct['bags_on_hand'] * (float) PT_GRAMS_PER_BAG, 2),
                'warned' => $deduct['warned'],
                'shortfall_bags' => $deduct['shortfall_bags'],
                'warning' => $deduct['warned']
                    ? 'Bag pool was short for this SKU stock-in; pool may be negative. Stock in more raw bags when you can.'
                    : null,
                'deduct_enabled' => true,
            ];
        } else {
            $onHand = dept_raw_bags_on_hand($pdo, 'pt');
            $bagPool = [
                'bags_deducted' => 0.0,
                'bags_on_hand' => $onHand,
                'grams_deducted' => 0.0,
                'grams_remaining' => round($onHand * (float) PT_GRAMS_PER_BAG, 2),
                'warned' => false,
                'shortfall_bags' => 0.0,
                'warning' => null,
                'deduct_enabled' => false,
            ];
        }
    } elseif ($productKey === 'brc') {
        require_once __DIR__ . '/dept_brc_settings.php';
        $gramsPerBag = dept_brc_setting_grams_per_bag($pdo);
        if (dept_brc_setting_bag_pool_deduct_enabled($pdo)) {
            $bags = dept_brc_setting_bags_per_pouch($pdo) * $qty;
            $deduct = dept_raw_deduct_bags_allow_negative($pdo, $userId, 'brc', $bags, 'sku_pouch_scan');
            $bagPool = [
                'bags_deducted' => $deduct['bags_deducted'],
                'bags_on_hand' => $deduct['bags_on_hand'],
                'grams_deducted' => round(dept_brc_setting_grams_per_pouch($pdo) * $qty, 2),
                'grams_remaining' => round($deduct['bags_on_hand'] * $gramsPerBag, 2),
                'warned' => $deduct['warned'],
                'shortfall_bags' => $deduct['shortfall_bags'],
                'warning' => $deduct['warned']
                    ? 'Bag pool was short for this SKU stock-in; pool may be negative. Stock in more raw bags when you can.'
                    : null,
                'deduct_enabled' => true,
            ];
        } else {
            $onHand = dept_raw_bags_on_hand($pdo, 'brc');
            $bagPool = [
                'bags_deducted' => 0.0,
                'bags_on_hand' => $onHand,
                'grams_deducted' => 0.0,
                'grams_remaining' => round($onHand * $gramsPerBag, 2),
                'warned' => false,
                'shortfall_bags' => 0.0,
                'warning' => null,
                'deduct_enabled' => false,
            ];
        }
    } elseif ($productKey === 'jar_cacao') {
        require_once __DIR__ . '/dept_jar_cacao_settings.php';
        $gramsPerBag = dept_jar_cacao_setting_grams_per_bag($pdo);
        if (dept_jar_cacao_setting_bag_pool_deduct_enabled($pdo)) {
            $bags = dept_jar_cacao_setting_bags_per_jar($pdo) * $qty;
            $deduct = dept_raw_deduct_bags_allow_negative($pdo, $userId, 'jar_cacao', $bags, 'sku_jar_scan');
            $bagPool = [
                'bags_deducted' => $deduct['bags_deducted'],
                'bags_on_hand' => $deduct['bags_on_hand'],
                'grams_deducted' => round(dept_jar_cacao_setting_grams_per_jar($pdo) * $qty, 2),
                'grams_remaining' => round($deduct['bags_on_hand'] * $gramsPerBag, 2),
                'warned' => $deduct['warned'],
                'shortfall_bags' => $deduct['shortfall_bags'],
                'warning' => $deduct['warned']
                    ? 'Bag pool was short for this SKU stock-in; pool may be negative. Stock in more raw bags when you can.'
                    : null,
                'deduct_enabled' => true,
            ];
        } else {
            $onHand = dept_raw_bags_on_hand($pdo, 'jar_cacao');
            $bagPool = [
                'bags_deducted' => 0.0,
                'bags_on_hand' => $onHand,
                'grams_deducted' => 0.0,
                'grams_remaining' => round($onHand * $gramsPerBag, 2),
                'warned' => false,
                'shortfall_bags' => 0.0,
                'warning' => null,
                'deduct_enabled' => false,
            ];
        }
    }

    $sku = sku_barcode_fetch_dept($pdo, $productKey);
    $credit = ['primary_request_id' => null, 'credited' => $qty];
    if ($sku !== null) {
        $credit = sku_barcode_credit_dept_requests(
            $pdo,
            $userId,
            (int) $sku['id'],
            $productKey,
            $qty
        );
    }

    $out = [
        'kind' => 'dept',
        'product_key' => $productKey,
        'product_label' => $label,
        'label' => $label,
        'qty' => $qty,
        'primary_request_id' => $credit['primary_request_id'],
    ];
    if ($bagPool !== null) {
        $out['bag_pool'] = $bagPool;
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function sku_barcode_rts_stock_in(
    PDO $pdo,
    int $userId,
    string $kind,
    ?string $variant,
    ?string $size,
    ?string $productKey,
    int $qty
): array {
    require_once __DIR__ . '/rts_inventory_sync.php';
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    if ($kind === 'honey') {
        $variant = honey_normalize_variant((string) $variant);
        $size = trim((string) $size);
        if (!in_array($size, HONEY_SIZES, true)) {
            throw new RuntimeException('Invalid size.');
        }
        rts_inventory_credit_sellable_honey($pdo, $variant, $size, (float) $qty);

        return [
            'kind' => 'honey',
            'variant' => $variant,
            'size' => $size,
            'size_label' => honey_bottle_size_label($size),
            'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),
            'qty' => $qty,
            'rts_stock_in' => true,
        ];
    }
    $productKey = strtolower(trim((string) $productKey));
    if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
        throw new RuntimeException('Invalid product.');
    }
    require_once __DIR__ . '/dept_pouch_barcodes.php';
    $label = dept_pouch_product_label($productKey);
    rts_inventory_credit_sellable_other($pdo, $label, (float) $qty);

    return [
        'kind' => 'dept',
        'product_key' => $productKey,
        'product_label' => $label,
        'label' => $label,
        'qty' => $qty,
        'rts_stock_in' => true,
    ];
}

/**
 * Deduct packaging / RTS / finished by SKU qty (POS / pack confirm).
 */
function sku_barcode_honey_deduct(
    PDO $pdo,
    int $userId,
    string $variant,
    string $size,
    int $qty,
    string $pool,
    ?int $packagingRowId,
    string $saleType,
    ?int $saleId = null,
    ?int $packSessionId = null
): void {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/rts_inventory_sync.php';
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid size.');
    }
    if ($qty < 1) {
        throw new RuntimeException('Quantity must be at least 1.');
    }
    if ($pool === 'rts_sellable') {
        rts_inventory_deduct_sell_honey_variant(
            $pdo,
            $userId,
            $variant,
            [$size => (float) $qty],
            $saleType,
            'sellable',
            [
                'used_where' => $saleType === 'walk_in' ? 'pos' : 'packaging',
                'pos_sale_id' => $saleId,
                'actor_user_id' => $userId > 0 ? $userId : null,
            ]
        );
    } else {
        if ($packagingRowId === null || $packagingRowId < 1) {
            // Deduct from any packaging rows with stock for this variant+size (FIFO by id).
            $remaining = $qty;
            [$curCol] = honey_packaging_size_columns($size);
            $st = $pdo->prepare(
                "SELECT id, {$curCol} AS on_hand FROM honey_packaging_rows
                 WHERE variant = ? AND {$curCol} > 0
                 ORDER BY id ASC FOR UPDATE"
            );
            $st->execute([$variant]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($remaining < 1) {
                    break;
                }
                $onHand = (int) floor((float) $row['on_hand']);
                $take = min($remaining, $onHand);
                if ($take < 1) {
                    continue;
                }
                honey_bottle_adjust_aggregate($pdo, (int) $row['id'], $size, -(float) $take);
                $remaining -= $take;
            }
            if ($remaining > 0) {
                throw new RuntimeException(
                    'Not enough packaging stock for ' . ucfirst($variant) . ' ' . honey_bottle_size_label($size) . '.'
                );
            }
        } else {
            honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, -(float) $qty);
        }
    }

    $hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');
    honey_bottle_write_sale_log($pdo, [
        'source' => $pool === 'rts_sellable' ? 'rts' : 'packaging',
        'packaging_row_id' => $packagingRowId !== null && $packagingRowId > 0 ? $packagingRowId : null,
        'batch_no' => null,
        'variant' => $variant,
        'size' => $size,
        'qty' => $qty,
        'sale_type' => $saleType,
        'sold_by' => $userId > 0 ? $userId : 1,
        'pos_sale_id' => $saleId,
        'bottle_unit_id' => null,
        'pack_session_id' => $hasSessCol && $packSessionId !== null ? $packSessionId : null,
    ]);
}

function sku_barcode_dept_deduct(
    PDO $pdo,
    int $userId,
    string $productKey,
    int $qty,
    string $pool,
    string $saleType
): void {
    require_once __DIR__ . '/dept_pouch_barcodes.php';
    require_once __DIR__ . '/rts_inventory_sync.php';
    $productKey = strtolower(trim($productKey));
    if ($qty < 1) {
        throw new RuntimeException('Quantity must be at least 1.');
    }
    if ($pool === 'rts_sellable') {
        rts_inventory_deduct_sell_other_label(
            $pdo,
            $userId,
            dept_pouch_product_label($productKey),
            (float) $qty,
            $saleType,
            'sellable',
            [
                'used_where' => $saleType === 'walk_in' ? 'pos' : 'packaging',
                'actor_user_id' => $userId > 0 ? $userId : null,
            ]
        );
    } else {
        dept_inventory_deduct($pdo, $productKey, (float) $qty);
    }
}

/**
 * Credit back after cancel (SKU path).
 */
function sku_barcode_honey_credit(
    PDO $pdo,
    string $variant,
    string $size,
    int $qty,
    string $pool,
    ?int $packagingRowId
): void {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/rts_inventory_sync.php';
    $variant = honey_normalize_variant($variant);
    if ($qty < 1) {
        return;
    }
    if ($pool === 'rts_sellable') {
        rts_inventory_credit_sellable_honey($pdo, $variant, $size, (float) $qty);
    } elseif ($packagingRowId !== null && $packagingRowId > 0) {
        honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, (float) $qty);
    } else {
        throw new RuntimeException('Cannot restock SKU honey without a packaging batch or RTS pool.');
    }
}

function sku_barcode_dept_credit(PDO $pdo, int $userId, string $productKey, int $qty, string $pool): void
{
    require_once __DIR__ . '/dept_pouch_barcodes.php';
    require_once __DIR__ . '/rts_inventory_sync.php';
    if ($qty < 1) {
        return;
    }
    if ($pool === 'rts_sellable') {
        rts_inventory_credit_sellable_other($pdo, dept_pouch_product_label($productKey), (float) $qty);
    } else {
        $fin = dept_inventory_ensure_row($pdo, $userId, $productKey);
        $label = dept_pouch_product_label($productKey);
        $pdo->prepare(
            'UPDATE dept_inventory_rows
             SET current_qty = current_qty + ?, product_label = ?, last_stock_in_at = NOW()
             WHERE id = ?'
        )->execute([$qty, $label, (int) $fin['id']]);
    }
}

function sku_barcode_record_print(
    PDO $pdo,
    int $userId,
    int $skuId,
    int $qty,
    string $requestKind = 'ad_hoc',
    ?int $honeyRequestId = null,
    ?int $deptRequestId = null,
    ?int $rtsLabelJobId = null
): int {
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Print quantity must be between 1 and 5000.');
    }
    if (sku_barcode_fetch_by_id($pdo, $skuId) === null) {
        throw new RuntimeException('SKU barcode is not configured. Set it in Barcode Settings first.');
    }
    $pdo->prepare(
        'INSERT INTO sku_barcode_print_jobs
            (sku_id, quantity, request_kind, honey_request_id, dept_request_id, rts_label_job_id, printed_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $skuId,
        $qty,
        $requestKind,
        $honeyRequestId,
        $deptRequestId,
        $rtsLabelJobId,
        $userId,
    ]);

    return (int) $pdo->lastInsertId();
}

/** A4 portrait label grid: 8 columns × 20 rows per page (285.6mm printable height). */
const SKU_LABEL_SHEET_COLS = 8;
const SKU_LABEL_SHEET_ROWS = 20;
const SKU_LABEL_SHEET_HEIGHT_MM = 285.6;

/**
 * Product text used by both the label cell and its request/banner row.
 *
 * @param array<string,mixed>|null $product
 */
function sku_barcode_product_info_text(?array $product): string
{
    if ($product === null) {
        return '';
    }
    $kind = (string) ($product['kind'] ?? '');
    if ($kind === 'honey') {
        $size = strtolower(trim((string) ($product['size'] ?? '')));
        $sizeLabel = match ($size) {
            'liter' => '1L',
            'gallon' => '1G',
            '250ml' => '250ml',
            '350ml' => '350ml',
            default => trim((string) ($product['size_label'] ?? $size)),
        };
        $variant = ucfirst(trim((string) ($product['variant'] ?? '')));

        return trim($sizeLabel . ' – ' . $variant, ' –');
    }
    if ($kind === 'dept') {
        return dept_pouch_product_label((string) ($product['product_key'] ?? ''));
    }

    return '';
}

/**
 * Render one product label as product info, Code128 barcode, and exact code.
 * Plain supplier/date labels also print the code under the bars for visibility.
 *
 * @param array<string,mixed>|null $product
 */
function sku_barcode_label_cell_html(string $code, ?array $product = null): string
{
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    try {
        $svg = honey_bottle_code128_svg($code);
    } catch (Throwable) {
        $svg = '<div class="err">barcode lib missing</div>';
    }
    $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $plain = '<div class="label label-plain">'
        . '<div class="bc" title="' . $safe . '">' . $svg . '</div>'
        . '<div class="human-code" title="' . $safe . '">' . $safe . '</div>'
        . '</div>';

    if ($product === null) {
        return $plain;
    }

    $topText = sku_barcode_product_info_text($product);
    if ($topText === '') {
        return $plain;
    }
    $safeTop = htmlspecialchars($topText, ENT_QUOTES, 'UTF-8');

    return '<div class="label label-product">'
        . '<div class="product-info" title="' . $safeTop . '">' . $safeTop . '</div>'
        . '<div class="bc" title="' . $safe . '">' . $svg . '</div>'
        . '<div class="human-code" title="' . $safe . '">' . $safe . '</div>'
        . '</div>';
}

function sku_barcode_label_banner_html(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return '<div class="banner" title="' . $safe . '"><div class="banner-inner">' . $safe . '</div></div>';
}

/**
 * Pack banner + label cells onto 8×20 A4 pages (continuous, batch banners start new rows).
 *
 * @param list<array{slots:int,html:string}> $stream
 */
function sku_barcode_render_packed_label_sheet(array $stream, string $title, int $labelCount = 0): string
{
    $cols = SKU_LABEL_SHEET_COLS;
    $rowsPerPage = SKU_LABEL_SHEET_ROWS;
    $sheetHeight = SKU_LABEL_SHEET_HEIGHT_MM;
    $rowMm = $sheetHeight / $rowsPerPage;

    $pages = [];
    $current = [];
    $col = 0;
    $rowsUsed = 0;

    $pushEmpty = static function () use (&$current, &$col, &$rowsUsed, $cols): void {
        $current[] = '<div class="label empty"></div>';
        $col++;
        if ($col >= $cols) {
            $col = 0;
            $rowsUsed++;
        }
    };

    $flushPage = static function () use (
        &$pages,
        &$current,
        &$col,
        &$rowsUsed,
        $cols,
        $rowsPerPage,
        $pushEmpty
    ): void {
        while ($col > 0) {
            $pushEmpty();
        }
        while ($rowsUsed < $rowsPerPage) {
            for ($i = 0; $i < $cols; $i++) {
                $current[] = '<div class="label empty"></div>';
            }
            $rowsUsed++;
        }
        $pages[] = $current;
        $current = [];
        $col = 0;
        $rowsUsed = 0;
    };

    $ensureRowStart = static function () use (&$col, $pushEmpty): void {
        while ($col > 0) {
            $pushEmpty();
        }
    };

    foreach ($stream as $item) {
        $need = (int) $item['slots'];
        if ($need >= $cols) {
            $ensureRowStart();
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
            $current[] = $item['html'];
            $rowsUsed++;
            $col = 0;
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
            continue;
        }
        if ($rowsUsed >= $rowsPerPage && $col === 0) {
            $flushPage();
        }
        if ($rowsUsed >= $rowsPerPage) {
            $flushPage();
        }
        $current[] = $item['html'];
        $col++;
        if ($col >= $cols) {
            $col = 0;
            $rowsUsed++;
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
        }
    }
    if ($current !== [] || $pages === []) {
        $flushPage();
    }

    $pagesHtml = '';
    foreach ($pages as $i => $chunk) {
        $pageClass = $i === 0 ? 'page' : 'page page-break';
        $pagesHtml .= '<section class="' . $pageClass . '"><div class="sheet">'
            . implode('', $chunk) . '</div></section>';
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $slotsPerPage = $cols * $rowsPerPage;

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>'
        . $safeTitle
        . '</title><style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #fff; color: #111; }
        .toolbar { margin: 10px 12px 0; }
        .toolbar button { font-size: 14px; padding: 8px 14px; cursor: pointer; }
        .toolbar .hint { font-size: 12px; color: #666; margin-left: 10px; }
        .page { width: 200mm; margin: 0 auto; padding: 0; page-break-inside: avoid; break-inside: avoid; }
        .sheet {
          display: grid;
          grid-template-columns: repeat(' . $cols . ', 1fr);
          grid-auto-rows: ' . $rowMm . 'mm;
          gap: 0;
          width: 100%;
          height: ' . $sheetHeight . 'mm;
          max-height: ' . $sheetHeight . 'mm;
          overflow: hidden;
          border-top: 0.35pt dashed #999;
          border-left: 0.35pt dashed #999;
          page-break-inside: avoid;
          break-inside: avoid;
        }
        .label, .banner {
          height: ' . $rowMm . 'mm;
          max-height: ' . $rowMm . 'mm;
          border: none;
          border-right: 0.35pt dashed #999;
          border-bottom: 0.35pt dashed #999;
          padding: 1.2mm 1mm;
          text-align: center;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          overflow: hidden;
          break-inside: avoid;
          page-break-inside: avoid;
        }
        .banner {
          grid-column: 1 / -1;
          background: #f3f3f3;
          padding: 1.5mm 3mm;
        }
        .banner-inner {
          font-size: 9pt;
          font-weight: 700;
          letter-spacing: 0.3pt;
          line-height: 1.2;
          width: 100%;
          text-align: left;
        }
        .label.empty .bc { visibility: hidden; }
        .label.empty .human-code { visibility: hidden; }
        .label-plain {
          padding: 0.6mm 0.7mm;
          justify-content: space-between;
          gap: 0.25mm;
        }
        .label-product {
          padding: 0.45mm 0.7mm;
          justify-content: space-between;
          gap: 0.2mm;
        }
        .product-info {
          width: 100%;
          min-height: 2.3mm;
          font-size: 6.5pt;
          font-weight: 700;
          line-height: 1;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .bc { line-height: 0; width: 100%; max-height: 11mm; overflow: hidden; }
        .label-plain .bc,
        .label-product .bc {
          flex: 1 1 auto;
          min-height: 0;
          max-height: 8.5mm;
          display: flex;
          align-items: center;
        }
        .human-code {
          width: 100%;
          min-height: 2.2mm;
          font-family: "Courier New", Courier, monospace;
          font-size: 6.2pt;
          font-weight: 700;
          letter-spacing: 0.2pt;
          line-height: 1;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .bc svg { width: 100%; max-height: 11mm; height: auto; display: block; }
        .label-plain .bc svg,
        .label-product .bc svg { max-height: 8.5mm; }
        .err { font-size: 6pt; color: #c00; }
        .page-break { page-break-before: always; break-before: page; }
        @media print {
          .toolbar { display: none; }
          body { margin: 0; }
          .page { width: auto; margin: 0; }
          .sheet, .banner {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
        </style></head><body>
        <div class="toolbar">
          <button type="button" onclick="window.print()">Print A4</button>
          <span class="hint">' . (int) $labelCount . ' label(s) · '
        . $cols . '×' . $rowsPerPage . ' (' . (int) $slotsPerPage . '/page) · barcode + code text · print at 100% scale</span>
        </div>
        ' . $pagesHtml . '
        </body></html>';
}

/**
 * @param list<array{sku_id:int,qty:int,banner?:string|null}> $groups
 */
function sku_barcode_combined_label_sheet_html(PDO $pdo, array $groups): string
{
    /** @var list<array{slots:int,html:string}> $stream */
    $stream = [];
    $labelCount = 0;
    $batchIndex = 0;

    foreach ($groups as $group) {
        $skuId = (int) ($group['sku_id'] ?? 0);
        $qty = (int) ($group['qty'] ?? 0);
        if ($skuId < 1 || $qty < 1) {
            continue;
        }
        $row = sku_barcode_fetch_by_id($pdo, $skuId);
        if ($row === null) {
            continue;
        }
        $code = (string) $row['code'];
        $batchIndex++;
        $letter = $batchIndex <= 26 ? chr(64 + $batchIndex) : (string) $batchIndex;
        $requestedBanner = isset($group['banner']) ? trim((string) $group['banner']) : '';
        $bannerText = $requestedBanner !== '' && !preg_match('/^BATCH(?:\s+[A-Z0-9]+)?$/i', $requestedBanner)
            ? $requestedBanner
            : sku_barcode_product_info_text($row);
        if ($bannerText === '') {
            $bannerText = 'BATCH ' . $letter;
        }

        $stream[] = [
            'slots' => SKU_LABEL_SHEET_COLS,
            'html' => sku_barcode_label_banner_html($bannerText),
        ];

        for ($i = 0; $i < $qty; $i++) {
            $stream[] = [
                'slots' => 1,
                'html' => sku_barcode_label_cell_html($code, $row),
            ];
            $labelCount++;
        }
    }

    if ($stream === []) {
        throw new RuntimeException('No labels to print.');
    }

    $title = 'SKU labels · ' . $batchIndex . ' batch(es) · ×' . $labelCount;

    return sku_barcode_render_packed_label_sheet($stream, $title, $labelCount);
}

function sku_barcode_label_sheet_html(PDO $pdo, int $skuId, int $qty): string
{
    if ($qty < 1) {
        throw new RuntimeException('Print quantity must be at least 1.');
    }
    if (sku_barcode_fetch_by_id($pdo, $skuId) === null) {
        throw new RuntimeException('SKU barcode not found.');
    }

    return sku_barcode_combined_label_sheet_html($pdo, [
        ['sku_id' => $skuId, 'qty' => $qty, 'banner' => 'BATCH A'],
    ]);
}

/**
 * @param list<array{sku_id:int,qty:int,banner?:string|null}> $groups
 */
function sku_barcode_print_sheet_url(array $groups): string
{
    $payload = [];
    foreach ($groups as $group) {
        $item = [
            'sku_id' => (int) ($group['sku_id'] ?? 0),
            'qty' => (int) ($group['qty'] ?? 0),
        ];
        if (isset($group['banner']) && trim((string) $group['banner']) !== '') {
            $item['banner'] = trim((string) $group['banner']);
        }
        $payload[] = $item;
    }

    return '/rts/api/v1/sku_barcodes.php?action=print_sheet&lines=' . rawurlencode(json_encode($payload));
}

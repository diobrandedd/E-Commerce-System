<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rts_storage.php';

const SUPPLIER_CATEGORIES = ['honey', 'paragis', 'black_rice', 'cacao'];

function supplier_display_name(string $first, string $last): string
{
    return trim($first . ' ' . $last);
}

function supplier_normalize_category(string $cat): string
{
    $cat = strtolower(trim($cat));
    if (!in_array($cat, SUPPLIER_CATEGORIES, true)) {
        throw new RuntimeException('Invalid supplier category.');
    }

    return $cat;
}

function supplier_category_label(string $cat): string
{
    return match ($cat) {
        'honey' => 'Honey',
        'paragis' => 'Paragis',
        'black_rice' => 'Black Rice',
        'cacao' => 'Cacao',
        default => $cat,
    };
}

/** @return array<string, mixed>|null */
function supplier_fetch(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** Resolve honey supplier_code (initials + MMDDYY) to master supplier record. */
function supplier_fetch_by_honey_code(PDO $pdo, string $supplierCode): ?array
{
    $code = trim($supplierCode);
    if ($code === '') {
        return null;
    }

    $st = $pdo->prepare(
        'SELECT s.* FROM suppliers s
         INNER JOIN honey_warehouse_rows w ON w.supplier_id = s.id
         WHERE w.supplier_code = ?
         ORDER BY w.id DESC
         LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch();

    return $row ?: null;
}

function supplier_serialize(array $row, bool $includePayment = true): array
{
    $out = [
        'id' => (int) $row['id'],
        'first_name' => (string) $row['first_name'],
        'last_name' => (string) $row['last_name'],
        'display_name' => supplier_display_name((string) $row['first_name'], (string) $row['last_name']),
        'contact_number' => (string) $row['contact_number'],
        'address' => (string) $row['address'],
        'category' => (string) $row['category'],
        'category_label' => supplier_category_label((string) $row['category']),
        'is_active' => (int) $row['is_active'] === 1,
        'valid_id_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_SUPPLIER_IDS, (string) $row['valid_id_filename'], '/rts/'),
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
    if ($includePayment) {
        $out['payment_account'] = (string) $row['payment_account'];
        $out['payment_account_number'] = (string) $row['payment_account_number'];
    }

    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function supplier_list(PDO $pdo, ?string $category, ?bool $activeOnly, ?string $q, bool $includePayment = false): array
{
    $sql = 'SELECT * FROM suppliers WHERE 1=1';
    $params = [];
    if ($category !== null && $category !== '') {
        $sql .= ' AND category = ?';
        $params[] = supplier_normalize_category($category);
    }
    if ($activeOnly === true) {
        $sql .= ' AND is_active = 1';
    }
    if ($q !== null && trim($q) !== '') {
        $term = '%' . trim($q) . '%';
        $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, " ", last_name) LIKE ?)';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    $sql .= ' ORDER BY last_name ASC, first_name ASC, id ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return array_map(fn ($r) => supplier_serialize($r, $includePayment), $st->fetchAll());
}

function supplier_store_valid_id_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Valid ID photo is required.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 12 * 1024 * 1024) {
        throw new RuntimeException('ID photo must be under 12MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => throw new RuntimeException('ID photo must be JPEG, PNG, or WebP.'),
    };
    $name = 'sid_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $dir = rts_storage_dir(RTS_STORAGE_BUCKET_SUPPLIER_IDS);
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new RuntimeException('Could not save ID photo.');
    }

    return $name;
}

function supplier_create(PDO $pdo, int $userId, array $fields, array $file): array
{
    $first = trim((string) ($fields['first_name'] ?? ''));
    $last = trim((string) ($fields['last_name'] ?? ''));
    $contact = trim((string) ($fields['contact_number'] ?? ''));
    $payAcct = trim((string) ($fields['payment_account'] ?? ''));
    $payNum = trim((string) ($fields['payment_account_number'] ?? ''));
    $address = trim((string) ($fields['address'] ?? ''));
    $category = supplier_normalize_category((string) ($fields['category'] ?? ''));

    if ($first === '' || $last === '') {
        throw new RuntimeException('First and last name are required.');
    }
    if ($contact === '' || $payAcct === '' || $payNum === '' || $address === '') {
        throw new RuntimeException('Contact, payment, and address are required.');
    }

    $idFile = supplier_store_valid_id_upload($file);

    $st = $pdo->prepare(
        'INSERT INTO suppliers (first_name, last_name, contact_number, payment_account, payment_account_number, address, valid_id_filename, category, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$first, $last, $contact, $payAcct, $payNum, $address, $idFile, $category, $userId]);

    $row = supplier_fetch($pdo, (int) $pdo->lastInsertId());

    return supplier_serialize($row, true);
}

function supplier_update(PDO $pdo, int $id, array $fields, ?array $file): array
{
    $row = supplier_fetch($pdo, $id);
    if (!$row) {
        throw new RuntimeException('Supplier not found.');
    }

    $first = trim((string) ($fields['first_name'] ?? $row['first_name']));
    $last = trim((string) ($fields['last_name'] ?? $row['last_name']));
    $contact = trim((string) ($fields['contact_number'] ?? $row['contact_number']));
    $payAcct = trim((string) ($fields['payment_account'] ?? $row['payment_account']));
    $payNum = trim((string) ($fields['payment_account_number'] ?? $row['payment_account_number']));
    $address = trim((string) ($fields['address'] ?? $row['address']));
    $category = supplier_normalize_category((string) ($fields['category'] ?? $row['category']));

    if ($first === '' || $last === '') {
        throw new RuntimeException('First and last name are required.');
    }

    $idFile = (string) $row['valid_id_filename'];
    if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $idFile = supplier_store_valid_id_upload($file);
    }

    $pdo->prepare(
        'UPDATE suppliers SET first_name = ?, last_name = ?, contact_number = ?, payment_account = ?,
         payment_account_number = ?, address = ?, valid_id_filename = ?, category = ? WHERE id = ?'
    )->execute([$first, $last, $contact, $payAcct, $payNum, $address, $idFile, $category, $id]);

    return supplier_serialize(supplier_fetch($pdo, $id), true);
}

function supplier_set_active(PDO $pdo, int $id, bool $active): array
{
    $pdo->prepare('UPDATE suppliers SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    $row = supplier_fetch($pdo, $id);
    if (!$row) {
        throw new RuntimeException('Supplier not found.');
    }

    return supplier_serialize($row, true);
}

/** @return array<string, mixed> */
function supplier_require_honey_active(PDO $pdo, int $supplierId): array
{
    $row = supplier_fetch($pdo, $supplierId);
    if (!$row) {
        throw new RuntimeException('Supplier not found.');
    }
    if ((int) $row['is_active'] !== 1) {
        throw new RuntimeException('Supplier is inactive.');
    }
    if ((string) $row['category'] !== 'honey') {
        throw new RuntimeException('Supplier is not a Honey supplier.');
    }

    return $row;
}

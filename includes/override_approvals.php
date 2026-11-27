<?php

declare(strict_types=1);

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/mailer.php';

const OVERRIDE_APPROVAL_HR_USER_KEY = 'approval.hr_manager_user_id';
const OVERRIDE_APPROVAL_HR_EMAIL_KEY = 'approval.hr_manager_email';

const OVERRIDE_APPROVAL_TYPES = [
    'inventory_check_finalize',
    'dept_raw_pool_set',
    'packaging_scan_undo',
    'pack_session_undo',
];

/**
 * @return array{user_id: ?int, email: string, name: string, username: string}|null
 */
function override_approval_hr_manager(?PDO $pdo = null): ?array
{
    $pdo = $pdo ?? (function_exists('ims_pdo') ? ims_pdo() : null);
    if ($pdo === null) {
        return null;
    }
    $uid = (int) (app_setting_get($pdo, OVERRIDE_APPROVAL_HR_USER_KEY, '0') ?? '0');
    if ($uid < 1) {
        return null;
    }
    $email = trim((string) app_setting_get($pdo, OVERRIDE_APPROVAL_HR_EMAIL_KEY, ''));
    $st = $pdo->prepare(
        'SELECT id, username, name, email, is_active FROM users WHERE id = ? LIMIT 1'
    );
    $st->execute([$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false || !(int) ($row['is_active'] ?? 0)) {
        return null;
    }

    return [
        'user_id' => (int) $row['id'],
        'email' => $email !== '' ? $email : trim((string) ($row['email'] ?? '')),
        'name' => (string) ($row['name'] ?? ''),
        'username' => (string) ($row['username'] ?? ''),
    ];
}

/** @param array{id?:int|string} $user */
function override_approval_is_hr_manager(array $user, ?PDO $pdo = null): bool
{
    $hr = override_approval_hr_manager($pdo);
    if ($hr === null || $hr['user_id'] === null) {
        return false;
    }

    return (int) ($user['id'] ?? 0) === (int) $hr['user_id'];
}

/**
 * @return array{user_id: ?int, email: string, name: string, username: string, configured: bool}
 */
function override_approval_hr_manager_public(PDO $pdo): array
{
    $hr = override_approval_hr_manager($pdo);
    if ($hr === null) {
        return [
            'user_id' => null,
            'email' => (string) app_setting_get($pdo, OVERRIDE_APPROVAL_HR_EMAIL_KEY, ''),
            'name' => '',
            'username' => '',
            'configured' => false,
        ];
    }

    return [
        'user_id' => $hr['user_id'],
        'email' => $hr['email'],
        'name' => $hr['name'],
        'username' => $hr['username'],
        'configured' => $hr['email'] !== '',
    ];
}

/**
 * @return list<array{id:int,username:string,name:string,email:string,role:string}>
 */
function override_approval_hr_candidates(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT u.id, u.username, u.name, u.email, r.name AS role
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.is_active = 1
         ORDER BY u.name ASC, u.username ASC'
    );
    $out = [];
    foreach (($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'name' => (string) $row['name'],
            'email' => (string) ($row['email'] ?? ''),
            'role' => (string) ($row['role'] ?? ''),
        ];
    }

    return $out;
}

function override_approval_hr_manager_save(
    PDO $pdo,
    int $userId,
    string $email,
    ?int $actorId
): array {
    if ($userId < 1) {
        throw new RuntimeException('Select an HR manager user.');
    }
    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid approval email for the HR manager.');
    }
    $st = $pdo->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
    $st->execute([$userId]);
    if ($st->fetchColumn() === false) {
        throw new RuntimeException('Selected user was not found or is inactive.');
    }
    app_setting_set($pdo, OVERRIDE_APPROVAL_HR_USER_KEY, (string) $userId, $actorId);
    app_setting_set($pdo, OVERRIDE_APPROVAL_HR_EMAIL_KEY, $email, $actorId);

    return override_approval_hr_manager_public($pdo);
}

/** @param array<string, mixed> $row */
function override_approval_serialize(array $row): array
{
    $payload = [];
    if (!empty($row['payload_json'])) {
        $decoded = json_decode((string) $row['payload_json'], true);
        $payload = is_array($decoded) ? $decoded : [];
    }
    $result = null;
    if (!empty($row['result_json'])) {
        $decoded = json_decode((string) $row['result_json'], true);
        $result = is_array($decoded) ? $decoded : null;
    }

    return [
        'id' => (int) $row['id'],
        'action_type' => (string) $row['action_type'],
        'payload' => $payload,
        'summary' => (string) $row['summary'],
        'requested_by' => (int) $row['requested_by'],
        'requested_by_name' => (string) ($row['requested_by_name'] ?? ''),
        'status' => (string) $row['status'],
        'reviewed_by' => $row['reviewed_by'] !== null ? (int) $row['reviewed_by'] : null,
        'reviewed_by_name' => (string) ($row['reviewed_by_name'] ?? ''),
        'reviewed_at' => $row['reviewed_at'] !== null ? (string) $row['reviewed_at'] : null,
        'rejection_note' => $row['rejection_note'] !== null ? (string) $row['rejection_note'] : null,
        'result' => $result,
        'error_message' => $row['error_message'] !== null ? (string) $row['error_message'] : null,
        'hr_viewed_at' => $row['hr_viewed_at'] !== null ? (string) $row['hr_viewed_at'] : null,
        'created_at' => (string) $row['created_at'],
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array{id:int,name?:string} $user
 * @return array{request: array<string,mixed>, email_sent: bool, email_error: ?string}
 */
function override_approval_create(
    PDO $pdo,
    array $user,
    string $actionType,
    array $payload,
    string $summary
): array {
    if (!in_array($actionType, OVERRIDE_APPROVAL_TYPES, true)) {
        throw new RuntimeException('Invalid approval action type.');
    }
    $hr = override_approval_hr_manager($pdo);
    if ($hr === null || $hr['user_id'] === null) {
        throw new RuntimeException('Set an HR manager in System Settings first.');
    }
    if ($hr['email'] === '') {
        throw new RuntimeException('HR manager approval email is not set in System Settings.');
    }
    $summary = trim($summary);
    if ($summary === '') {
        throw new RuntimeException('Summary is required.');
    }
    if (function_exists('mb_substr')) {
        $summary = mb_substr($summary, 0, 500);
    } else {
        $summary = substr($summary, 0, 500);
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Could not encode approval payload.');
    }
    $requesterId = (int) ($user['id'] ?? 0);
    if ($requesterId < 1) {
        throw new RuntimeException('Requester required.');
    }

    $pdo->prepare(
        'INSERT INTO override_approval_requests
            (action_type, payload_json, summary, requested_by, status)
         VALUES (?, ?, ?, ?, "pending")'
    )->execute([$actionType, $json, $summary, $requesterId]);
    $id = (int) $pdo->lastInsertId();

    $link = app_mail_spa_base_url($pdo) . '/approvals?id=' . $id;
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $hrNamePlain = $hr['name'] !== '' ? $hr['name'] : 'HR manager';
    $requesterName = trim((string) ($user['name'] ?? '')) !== ''
        ? (string) $user['name']
        : ('User #' . $requesterId);
    $safeHr = htmlspecialchars($hrNamePlain, ENT_QUOTES, 'UTF-8');
    $safeRequester = htmlspecialchars($requesterName, ENT_QUOTES, 'UTF-8');
    $safeSummary = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
    $subject = 'Pyx Tracker approval needed: ' . $summary;
    // Table-based button for Gmail/Outlook; gold matches SPA primary.
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#1f2937;">'
        . '<p style="margin:0 0 16px;">Hello ' . $safeHr . ',</p>'
        . '<p style="margin:0 0 8px;"><strong>' . $safeRequester . '</strong> requested an approval:</p>'
        . '<div style="margin:0 0 20px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;">'
        . '<p style="margin:0;font-size:14px;color:#111827;">' . $safeSummary . '</p>'
        . '</div>'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;">'
        . '<tr><td align="center" bgcolor="#C8900A" style="border-radius:8px;">'
        . '<a href="' . $safeLink . '" target="_blank"'
        . ' style="display:inline-block;padding:12px 22px;font-family:Arial,Helvetica,sans-serif;'
        . 'font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;">'
        . 'Review request</a>'
        . '</td></tr></table>'
        . '<p style="margin:0;font-size:12px;color:#6b7280;">You must log in. This button opens the Approvals page — it does not apply the change by itself.</p>'
        . '</div>';
    $text = "Hello {$hrNamePlain},\n\n{$requesterName} requested an approval:\n{$summary}\n\n"
        . "Review: {$link}\n\nYou must log in. The link alone does not apply the change.\n";
    $mail = app_mail_send($pdo, $hr['email'], $subject, $html, $text);

    $req = override_approval_get($pdo, $id);
    if ($req === null) {
        throw new RuntimeException('Approval request was created but could not be reloaded.');
    }

    return [
        'request' => $req,
        'email_sent' => !empty($mail['ok']),
        'email_error' => empty($mail['ok']) ? (string) ($mail['error'] ?? 'Email failed.') : null,
    ];
}

function override_approval_get(PDO $pdo, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT r.*,
                req.name AS requested_by_name,
                rev.name AS reviewed_by_name
         FROM override_approval_requests r
         LEFT JOIN users req ON req.id = r.requested_by
         LEFT JOIN users rev ON rev.id = r.reviewed_by
         WHERE r.id = ?
         LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    return override_approval_serialize($row);
}

/**
 * @return list<array<string,mixed>>
 */
function override_approval_list(
    PDO $pdo,
    array $viewer,
    string $status = 'all',
    int $limit = 50
): array {
    $limit = max(1, min(200, $limit));
    $isHr = override_approval_is_hr_manager($viewer, $pdo);
    $params = [];
    $where = [];
    if ($status !== '' && strtolower($status) !== 'all') {
        $where[] = 'r.status = ?';
        $params[] = strtolower($status);
    }
    if (!$isHr) {
        $where[] = 'r.requested_by = ?';
        $params[] = (int) ($viewer['id'] ?? 0);
    }
    $sql = 'SELECT r.*,
                   req.name AS requested_by_name,
                   rev.name AS reviewed_by_name
            FROM override_approval_requests r
            LEFT JOIN users req ON req.id = r.requested_by
            LEFT JOIN users rev ON rev.id = r.reviewed_by';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY r.created_at DESC, r.id DESC LIMIT ' . $limit;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[] = override_approval_serialize($row);
    }

    return $out;
}

function override_approval_pending_count(PDO $pdo): int
{
    $st = $pdo->query(
        'SELECT COUNT(*) FROM override_approval_requests WHERE status = "pending"'
    );

    return (int) ($st ? $st->fetchColumn() : 0);
}

/**
 * Unread pending for HR manager bell (not yet viewed).
 *
 * @return list<array<string,mixed>>
 */
function override_approval_unread_list(PDO $pdo, int $limit = 20): array
{
    $limit = max(1, min(50, $limit));
    $st = $pdo->prepare(
        'SELECT r.*,
                req.name AS requested_by_name,
                rev.name AS reviewed_by_name
         FROM override_approval_requests r
         LEFT JOIN users req ON req.id = r.requested_by
         LEFT JOIN users rev ON rev.id = r.reviewed_by
         WHERE r.status = "pending" AND r.hr_viewed_at IS NULL
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT ' . $limit
    );
    $st->execute();
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[] = override_approval_serialize($row);
    }

    return $out;
}

function override_approval_mark_viewed(PDO $pdo, ?int $id = null): void
{
    if ($id !== null && $id > 0) {
        $pdo->prepare(
            'UPDATE override_approval_requests
             SET hr_viewed_at = COALESCE(hr_viewed_at, NOW())
             WHERE id = ?'
        )->execute([$id]);

        return;
    }
    $pdo->exec(
        'UPDATE override_approval_requests
         SET hr_viewed_at = COALESCE(hr_viewed_at, NOW())
         WHERE status = "pending" AND hr_viewed_at IS NULL'
    );
}

/**
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function override_approval_execute(PDO $pdo, string $actionType, array $payload, array $actor): array
{
    $actorId = (int) ($actor['id'] ?? 0);
    if ($actorId < 1) {
        throw new RuntimeException('Actor required.');
    }

    if ($actionType === 'inventory_check_finalize') {
        require_once __DIR__ . '/inventory_check_apply.php';
        $rawIds = is_array($payload['event_ids'] ?? null) ? $payload['event_ids'] : [];
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
        if ($eventIds === []) {
            $one = (int) ($payload['event_id'] ?? 0);
            if ($one > 0) {
                $eventIds = [$one];
            }
        }
        if ($eventIds === []) {
            throw new RuntimeException('No audit selected.');
        }

        return inventory_check_finalize_with_sync($pdo, $actor, $eventIds, false);
    }

    if ($actionType === 'dept_raw_pool_set') {
        require_once __DIR__ . '/dept_inventory.php';
        $productKey = trim((string) ($payload['product_key'] ?? ''));
        $note = isset($payload['note']) ? (string) $payload['note'] : 'HR-approved leaf pool edit';
        if (array_key_exists('grams', $payload)) {
            return dept_raw_set_grams_on_hand(
                $pdo,
                $actorId,
                $productKey,
                (float) $payload['grams'],
                $note,
                'executive_edit'
            );
        }

        return dept_raw_set_bags_on_hand(
            $pdo,
            $actorId,
            $productKey,
            (float) ($payload['bags'] ?? -1),
            $note,
            'executive_edit'
        );
    }

    if ($actionType === 'packaging_scan_undo') {
        require_once __DIR__ . '/honey_bottle_barcodes.php';
        $scanDate = trim((string) ($payload['scan_date'] ?? ''));
        $variant = trim((string) ($payload['variant'] ?? ''));
        $size = trim((string) ($payload['size'] ?? ''));
        $scannedBy = null;
        if (array_key_exists('scanned_by', $payload) && $payload['scanned_by'] !== null && $payload['scanned_by'] !== '') {
            $scannedBy = (int) $payload['scanned_by'];
            if ($scannedBy < 1) {
                $scannedBy = null;
            }
        }
        $expectedQty = isset($payload['expected_qty']) ? (int) $payload['expected_qty'] : 0;
        if ($expectedQty > 0) {
            $current = override_approval_packaging_line_qty($pdo, $scanDate, $scannedBy, $variant, $size);
            if ($current !== $expectedQty) {
                throw new RuntimeException(
                    'Line changed since request; submit again. (was '
                    . $expectedQty . ', now ' . $current . ')'
                );
            }
        }

        return honey_bottle_packaging_scan_line_undo(
            $pdo,
            $actorId,
            $scanDate,
            $scannedBy,
            $variant,
            $size
        );
    }

    if ($actionType === 'pack_session_undo') {
        require_once __DIR__ . '/honey_bottle_barcodes.php';
        $sessionId = (int) ($payload['session_id'] ?? 0);
        if ($sessionId < 1) {
            throw new RuntimeException('Invalid pack session.');
        }
        $expectedCount = isset($payload['expected_item_count']) ? (int) $payload['expected_item_count'] : 0;
        $st = $pdo->prepare('SELECT status FROM honey_bottle_pack_sessions WHERE id = ? LIMIT 1');
        $st->execute([$sessionId]);
        $status = (string) ($st->fetchColumn() ?: '');
        if ($status !== 'confirmed') {
            throw new RuntimeException(
                $status === 'undone'
                    ? 'This pack session was already undone.'
                    : 'Session changed since request; submit again.'
            );
        }
        if ($expectedCount > 0) {
            $current = honey_bottle_pack_session_item_count($pdo, $sessionId);
            if ($current !== $expectedCount) {
                throw new RuntimeException(
                    'Session changed since request; submit again. (was '
                    . $expectedCount . ' lines, now ' . $current . ')'
                );
            }
        }

        return honey_bottle_pack_session_undo($pdo, $actorId, $sessionId);
    }

    throw new RuntimeException('Unknown approval action.');
}

function override_approval_packaging_line_qty(
    PDO $pdo,
    string $scanDate,
    ?int $scannedBy,
    string $variant,
    string $size
): int {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    require_once __DIR__ . '/honey_volume.php';
    $scanDateNorm = honey_bottle_scan_report_date($scanDate);
    if ($scanDateNorm === null) {
        return 0;
    }
    $variant = honey_normalize_variant($variant);
    $size = honey_normalize_bottle_size($size);
    $from = $scanDateNorm . ' 00:00:00';
    $to = (new DateTimeImmutable($scanDateNorm))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

    $bySql = $scannedBy === null ? 'c.scanned_by IS NULL' : 'c.scanned_by = ?';
    $params = [$from, $to, $variant, $size];
    if ($scannedBy !== null) {
        $params[] = $scannedBy;
    }
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         WHERE c.scanned_at IS NOT NULL
           AND c.scanned_at >= ? AND c.scanned_at < ?
           AND LOWER(b.variant) = ? AND LOWER(c.size) = ?
           AND {$bySql}"
    );
    $st->execute($params);
    $stickers = (int) $st->fetchColumn();

    $skuBy = $scannedBy === null ? 'e.scanned_by IS NULL' : 'e.scanned_by = ?';
    $skuParams = [$from, $to, $variant, $size];
    if ($scannedBy !== null) {
        $skuParams[] = $scannedBy;
    }
    $skuSt = $pdo->prepare(
        "SELECT COALESCE(SUM(e.qty), 0)
         FROM sku_barcode_stock_events e
         INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
         WHERE e.kind = 'honey' AND e.packaging_row_id IS NOT NULL
           AND e.scanned_at >= ? AND e.scanned_at < ?
           AND LOWER(s.variant) = ? AND LOWER(s.size) = ?
           AND {$skuBy}"
    );
    $skuSt->execute($skuParams);

    return $stickers + (int) $skuSt->fetchColumn();
}

/**
 * @param array{id:int,name?:string} $reviewer
 * @return array<string, mixed>
 */
function override_approval_approve(PDO $pdo, int $id, array $reviewer): array
{
    if (!override_approval_is_hr_manager($reviewer, $pdo)) {
        throw new RuntimeException('Only the HR manager can approve.');
    }
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    $failMessage = null;
    try {
        $st = $pdo->prepare(
            'SELECT * FROM override_approval_requests WHERE id = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Approval request not found.');
        }
        if ((string) $row['status'] !== 'pending') {
            throw new RuntimeException('This request is no longer pending.');
        }
        $payload = json_decode((string) $row['payload_json'], true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid request payload.');
        }
        $actionType = (string) $row['action_type'];
        // Apply as the original requester so feature permission gates still match who submitted.
        $actor = [
            'id' => (int) $row['requested_by'],
            'name' => '',
            'role' => '',
        ];
        $actorSt = $pdo->prepare(
            'SELECT u.id, u.name, r.name AS role
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?
             LIMIT 1'
        );
        $actorSt->execute([(int) $row['requested_by']]);
        $actorRow = $actorSt->fetch(PDO::FETCH_ASSOC);
        if ($actorRow !== false) {
            $actor = [
                'id' => (int) $actorRow['id'],
                'name' => (string) ($actorRow['name'] ?? ''),
                'role' => (string) ($actorRow['role'] ?? ''),
            ];
        }
        try {
            $result = override_approval_execute($pdo, $actionType, $payload, $actor);
            $encoded = json_encode($result, JSON_UNESCAPED_UNICODE);
            $pdo->prepare(
                'UPDATE override_approval_requests
                 SET status = "approved",
                     reviewed_by = ?,
                     reviewed_at = NOW(),
                     result_json = ?,
                     error_message = NULL,
                     hr_viewed_at = COALESCE(hr_viewed_at, NOW())
                 WHERE id = ?'
            )->execute([(int) $reviewer['id'], $encoded !== false ? $encoded : null, $id]);
        } catch (Throwable $e) {
            $failMessage = $e->getMessage();
            if (function_exists('mb_substr')) {
                $failMessage = mb_substr($failMessage, 0, 500);
            } else {
                $failMessage = substr($failMessage, 0, 500);
            }
            $pdo->prepare(
                'UPDATE override_approval_requests
                 SET status = "failed",
                     reviewed_by = ?,
                     reviewed_at = NOW(),
                     error_message = ?,
                     hr_viewed_at = COALESCE(hr_viewed_at, NOW())
                 WHERE id = ?'
            )->execute([(int) $reviewer['id'], $failMessage, $id]);
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

    $out = override_approval_get($pdo, $id);
    if ($out === null) {
        throw new RuntimeException('Could not reload approval request.');
    }
    if ($failMessage !== null) {
        throw new RuntimeException($failMessage);
    }

    return $out;
}

/**
 * @param array{id:int} $reviewer
 * @return array<string, mixed>
 */
function override_approval_reject(PDO $pdo, int $id, array $reviewer, string $note): array
{
    if (!override_approval_is_hr_manager($reviewer, $pdo)) {
        throw new RuntimeException('Only the HR manager can reject.');
    }
    $note = trim($note);
    if ($note === '') {
        throw new RuntimeException('A rejection note is required.');
    }
    if (function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 500);
    } else {
        $note = substr($note, 0, 500);
    }
    $upd = $pdo->prepare(
        'UPDATE override_approval_requests
         SET status = "rejected",
             reviewed_by = ?,
             reviewed_at = NOW(),
             rejection_note = ?,
             hr_viewed_at = COALESCE(hr_viewed_at, NOW())
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([(int) $reviewer['id'], $note, $id]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Request not found or no longer pending.');
    }
    $out = override_approval_get($pdo, $id);
    if ($out === null) {
        throw new RuntimeException('Rejected but could not reload request.');
    }

    return $out;
}

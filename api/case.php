<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/courier_detect.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rts_attachments.php';
require_once dirname(__DIR__) . '/includes/rts_shop_couriers.php';
require_once dirname(__DIR__) . '/includes/rts_claim_status.php';
require_once dirname(__DIR__) . '/includes/rts_products.php';

header('Content-Type: application/json; charset=utf-8');

rts_session_start();
$authUser = rts_auth_user();
if (!rts_can_access_rts_admin($authUser)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$allowedCouriers = ['spx', 'jnt', 'flash', 'unknown'];
$allowedShops = ['shopee', 'lazada', 'tiktok', 'vip'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $tracking = isset($_GET['tracking']) ? trim((string) $_GET['tracking']) : '';
    if ($tracking === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing tracking']);
        exit;
    }
    try {
        $pdo = rts_pdo();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
        exit;
    }
    $st = $pdo->prepare(
        'SELECT id, tracking_number, courier_detected, courier_final, shop, vip_subsource,
                date_delivered, date_opened, documentation_photo, documentation_attachments,
                damaged_reason, damaged_tag, delivery_match_item_id,
                created_at, updated_at, voided_at
         FROM rts_cases WHERE tracking_number = ? LIMIT 1'
    );
    $st->execute([$tracking]);
    $case = $st->fetch();
    if (!$case) {
        echo json_encode([
            'ok' => true,
            'exists' => false,
            'detected_courier' => rts_detect_courier($tracking),
            'case' => null,
            'lines' => [],
        ]);
        exit;
    }
    $lid = (int) $case['id'];
    $attRaw = $case['documentation_attachments'] ?? null;
    unset($case['documentation_attachments']);
    $case['documentation_attachments'] = rts_attachments_decode(
        $attRaw !== null && $attRaw !== '' ? (string) $attRaw : null,
        isset($case['documentation_photo']) ? (string) $case['documentation_photo'] : null
    );
    if (!isset($case['damaged_tag'])) {
        $case['damaged_tag'] = rts_damaged_tag(isset($case['damaged_reason']) ? (string) $case['damaged_reason'] : null);
    }
    $lines = $pdo->prepare('SELECT id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id, created_at FROM rts_line_items WHERE case_id = ? ORDER BY id ASC');
    $lines->execute([$lid]);
    echo json_encode([
        'ok' => true,
        'exists' => true,
        'detected_courier' => rts_detect_courier($tracking),
        'case' => $case,
        'lines' => $lines->fetchAll(),
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$action = isset($data['action']) ? (string) $data['action'] : '';
$tracking = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

if ($action === 'update_claim') {
    $caseId = isset($data['case_id']) ? (int) $data['case_id'] : 0;
    if ($caseId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid case_id']);
        exit;
    }
    $claimStatus = isset($data['claim_status']) ? trim((string) $data['claim_status']) : '';
    if (!rts_claim_status_valid($claimStatus)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid claim status']);
        exit;
    }
    $claimNote = isset($data['claim_status_note']) ? trim((string) $data['claim_status_note']) : '';
    if (strlen($claimNote) > 512) {
        $claimNote = substr($claimNote, 0, 512);
    }
    try {
        $st = $pdo->prepare('SELECT id FROM rts_cases WHERE id = ? LIMIT 1');
        $st->execute([$caseId]);
        if (!$st->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Case not found']);
            exit;
        }
        $upd = $pdo->prepare(
            'UPDATE rts_cases SET claim_status = ?, claim_status_note = ? WHERE id = ?'
        );
        $upd->execute([$claimStatus, $claimNote, $caseId]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not save claim status. Run sql/migration_rts_claim_status.sql']);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'claim_status' => $claimStatus,
        'claim_status_label' => rts_claim_status_label($claimStatus),
        'claim_status_note' => $claimNote,
    ]);
    exit;
}

if ($action === 'void') {
    $caseId = isset($data['case_id']) ? (int) $data['case_id'] : 0;
    if ($caseId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid case_id']);
        exit;
    }
    $st = $pdo->prepare(
        'UPDATE rts_cases SET voided_at = NOW(), voided_by_user_id = ? WHERE id = ? AND voided_at IS NULL'
    );
    $st->execute([(int) $authUser['id'], $caseId]);
    if ($st->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Case not found or already voided']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'Record voided']);
    exit;
}

if ($action === 'update_record') {
    $caseId = isset($data['case_id']) ? (int) $data['case_id'] : 0;
    if ($caseId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid case_id']);
        exit;
    }
    $tracking = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';
    if ($tracking === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing tracking_number']);
        exit;
    }

    $linesIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
    $err = rts_validate_lines_v2($linesIn);
    if ($err !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $err]);
        exit;
    }
    $lines = array_map('rts_normalize_line', $linesIn);

    $courierFinal = isset($data['courier_final']) ? (string) $data['courier_final'] : '';
    if ($courierFinal === '' || $courierFinal === 'unknown') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'courier_final is required']);
        exit;
    }
    if (!in_array($courierFinal, $allowedCouriers, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid courier_final']);
        exit;
    }
    $shop = isset($data['shop']) ? (string) $data['shop'] : '';
    if (!in_array($shop, $allowedShops, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid shop']);
        exit;
    }
    if (!rts_shop_allows_courier($shop, $courierFinal)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Courier not allowed for this shop']);
        exit;
    }
    $vip = null;
    if ($shop === 'vip') {
        $vs = isset($data['vip_subsource']) ? (string) $data['vip_subsource'] : '';
        if ($vs !== '' && !in_array($vs, ['website', 'facebook'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid vip_subsource']);
            exit;
        }
        $vip = $vs === '' ? null : $vs;
    }

    $dd = isset($data['date_delivered']) ? trim((string) $data['date_delivered']) : '';
    $do = isset($data['date_opened']) ? trim((string) $data['date_opened']) : '';
    if ($dd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'date_delivered is required']);
        exit;
    }
    if ($do === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $do)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'date_opened is required']);
        exit;
    }

    $normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);
    if ($normAtt['error'] !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $normAtt['error']]);
        exit;
    }
    $attachList = $normAtt['list'];
    if ($attachList === []) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'documentation_required',
            'message' => 'At least one RTS documentation photo or video is required.',
        ]);
        exit;
    }

    $attachmentsJson = json_encode($attachList, JSON_UNESCAPED_UNICODE);
    $docPhotoCol = rts_attachments_first_image($attachList);

    $damagedReason = isset($data['damaged_reason']) ? trim((string) $data['damaged_reason']) : '';
    $allowedDamaged = ['leak', 'item_replaced', 'packaging_replaced'];
    if ($damagedReason !== '' && !in_array($damagedReason, $allowedDamaged, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid damaged_reason']);
        exit;
    }

    $deliveryMatchItemId = null;
    if ($damagedReason !== '') {
        $m = $pdo->prepare(
            'SELECT i.id FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL
               AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
               AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
             ORDER BY b.submitted_at DESC, i.id DESC
             LIMIT 1'
        );
        $m->execute([$tracking]);
        $mid = $m->fetchColumn();
        if ($mid === false || (int) $mid < 1) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'damaged_requires_delivery_photo',
                'message' => 'Marking as damaged requires a pre-delivery photo with the same tracking in Deliveries. None was found.',
            ]);
            exit;
        }
        $deliveryMatchItemId = (int) $mid;
    }

    $damagedDb = $damagedReason === '' ? null : $damagedReason;
    $damagedTag = rts_damaged_tag($damagedDb);
    $detected = rts_detect_courier($tracking);

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'SELECT id, tracking_number FROM rts_cases WHERE id = ? AND voided_at IS NULL LIMIT 1'
        );
        $st->execute([$caseId]);
        $existing = $st->fetch();
        if (!$existing) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Case not found or voided']);
            exit;
        }

        $dup = $pdo->prepare(
            'SELECT id FROM rts_cases WHERE tracking_number = ? AND voided_at IS NULL AND id != ? LIMIT 1'
        );
        $dup->execute([$tracking, $caseId]);
        if ($dup->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'duplicate_tracking', 'message' => 'Another record already uses this tracking number.']);
            exit;
        }

        $upd = $pdo->prepare(
            'UPDATE rts_cases SET tracking_number = ?, courier_detected = ?, courier_final = ?, shop = ?, vip_subsource = ?,
                date_delivered = ?, date_opened = ?, documentation_photo = ?, documentation_attachments = ?,
                damaged_reason = ?, damaged_tag = ?, delivery_match_item_id = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $upd->execute([
            $tracking,
            $detected,
            $courierFinal,
            $shop,
            $vip,
            $dd,
            $do,
            $docPhotoCol,
            $attachmentsJson,
            $damagedDb,
            $damagedTag,
            $deliveryMatchItemId,
            $caseId,
        ]);

        $pdo->prepare('DELETE FROM rts_line_items WHERE case_id = ?')->execute([$caseId]);

        $li = $pdo->prepare(
            'INSERT INTO rts_line_items (case_id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $insertedIds = [];
        foreach ($lines as $idx => $L) {
            $parentId = null;
            if ($L['is_bonus'] && isset($L['_bonus_parent_idx'])) {
                $parentId = $insertedIds[(int) $L['_bonus_parent_idx']] ?? null;
            }
            $li->execute([
                $caseId,
                $L['product_type'],
                $L['honey_size'],
                $L['honey_variant'],
                $L['combo_product'],
                $L['combo_quantity'],
                $L['combo_honey_size'],
                $L['combo_honey_variant'],
                $L['quantity'],
                $L['is_bonus'],
                $parentId,
            ]);
            $insertedIds[$idx] = (int) $pdo->lastInsertId();
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Update failed']);
        exit;
    }

    echo json_encode(['ok' => true, 'case_id' => $caseId]);
    exit;
}

if ($tracking === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing tracking_number']);
    exit;
}

// Validation and normalization now handled by includes/rts_products.php

if ($action === 'create') {
    $linesIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
    $err = rts_validate_lines_v2($linesIn);
    if ($err !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $err]);
        exit;
    }
    $lines = array_map('rts_normalize_line', $linesIn);

    $courierFinal = isset($data['courier_final']) ? (string) $data['courier_final'] : '';
    if ($courierFinal === '' || $courierFinal === 'unknown') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'courier_final is required']);
        exit;
    }
    if (!in_array($courierFinal, $allowedCouriers, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid courier_final']);
        exit;
    }
    $shop = isset($data['shop']) ? (string) $data['shop'] : '';
    if (!in_array($shop, $allowedShops, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid shop']);
        exit;
    }
    if (!rts_shop_allows_courier($shop, $courierFinal)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Courier not allowed for this shop']);
        exit;
    }
    $vip = null;
    if ($shop === 'vip') {
        $vs = isset($data['vip_subsource']) ? (string) $data['vip_subsource'] : '';
        if ($vs !== '' && !in_array($vs, ['website', 'facebook'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid vip_subsource']);
            exit;
        }
        $vip = $vs === '' ? null : $vs;
    }

    $dd = isset($data['date_delivered']) ? trim((string) $data['date_delivered']) : '';
    $do = isset($data['date_opened']) ? trim((string) $data['date_opened']) : '';
    $docPhoto = isset($data['documentation_photo']) ? trim((string) $data['documentation_photo']) : '';
    if ($dd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'date_delivered is required']);
        exit;
    }
    if ($do === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $do)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'date_opened is required']);
        exit;
    }
    $dateDelivered = $dd;
    $dateOpened = $do;

    $normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);
    if ($normAtt['error'] !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $normAtt['error']]);
        exit;
    }
    $attachList = $normAtt['list'];
    if ($attachList === [] && $docPhoto !== '') {
        if (!rts_attachment_filename_valid($docPhoto)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid documentation_photo filename']);
            exit;
        }
        $attachList[] = ['kind' => 'image', 'file' => $docPhoto];
    }

    if ($attachList === []) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'documentation_required',
            'message' => 'At least one RTS documentation photo or video is required.',
        ]);
        exit;
    }

    $attachmentsJson = json_encode($attachList, JSON_UNESCAPED_UNICODE);
    $docPhotoCol = rts_attachments_first_image($attachList);
    $damagedReason = isset($data['damaged_reason']) ? trim((string) $data['damaged_reason']) : '';
    $allowedDamaged = ['leak', 'item_replaced', 'packaging_replaced'];
    if ($damagedReason !== '' && !in_array($damagedReason, $allowedDamaged, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid damaged_reason']);
        exit;
    }

    $deliveryMatchItemId = null;
    if ($damagedReason !== '') {
        $m = $pdo->prepare(
            'SELECT i.id FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL
               AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
               AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
             ORDER BY b.submitted_at DESC, i.id DESC
             LIMIT 1'
        );
        $m->execute([$tracking]);
        $mid = $m->fetchColumn();
        if ($mid === false || (int) $mid < 1) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'damaged_requires_delivery_photo',
                'message' => 'Marking as damaged requires a pre-delivery photo with the same tracking in Deliveries. None was found.',
            ]);
            exit;
        }
        $deliveryMatchItemId = (int) $mid;
    }

    $damagedDb = $damagedReason === '' ? null : $damagedReason;
    $damagedTag = rts_damaged_tag($damagedDb);

    $detected = rts_detect_courier($tracking);

    $pdo->beginTransaction();
    try {
        $check = $pdo->prepare('SELECT id FROM rts_cases WHERE tracking_number = ? AND voided_at IS NULL LIMIT 1');
        $check->execute([$tracking]);
        if ($check->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'duplicate_tracking', 'message' => 'This tracking number already exists.']);
            exit;
        }
        $ins = $pdo->prepare(
            'INSERT INTO rts_cases (tracking_number, courier_detected, courier_final, shop, vip_subsource, date_delivered, date_opened, documentation_photo, documentation_attachments, damaged_reason, damaged_tag, delivery_match_item_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $tracking,
            $detected,
            $courierFinal,
            $shop,
            $vip,
            $dateDelivered,
            $dateOpened,
            $docPhotoCol,
            $attachmentsJson,
            $damagedDb,
            $damagedTag,
            $deliveryMatchItemId,
        ]);
        $caseId = (int) $pdo->lastInsertId();
        $li = $pdo->prepare(
            'INSERT INTO rts_line_items (case_id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $insertedIds = [];
        foreach ($lines as $idx => $L) {
            $parentId = null;
            if ($L['is_bonus'] && isset($L['_bonus_parent_idx'])) {
                $parentId = $insertedIds[(int) $L['_bonus_parent_idx']] ?? null;
            }
            $li->execute([
                $caseId,
                $L['product_type'],
                $L['honey_size'],
                $L['honey_variant'],
                $L['combo_product'],
                $L['combo_quantity'],
                $L['combo_honey_size'],
                $L['combo_honey_variant'],
                $L['quantity'],
                $L['is_bonus'],
                $parentId,
            ]);
            $insertedIds[$idx] = (int) $pdo->lastInsertId();
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Save failed']);
        exit;
    }

    echo json_encode(['ok' => true, 'case_id' => $caseId]);
    exit;
}

if ($action === 'append_lines') {
    $linesIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
    $err = rts_validate_lines_v2($linesIn);
    if ($err !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $err]);
        exit;
    }
    $lines = array_map('rts_normalize_line', $linesIn);

    $st = $pdo->prepare('SELECT id FROM rts_cases WHERE tracking_number = ? AND voided_at IS NULL LIMIT 1');
    $st->execute([$tracking]);
    $row = $st->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Case not found, voided, or invalid tracking number']);
        exit;
    }
    $caseId = (int) $row['id'];
    $pdo->beginTransaction();
    try {
        $li = $pdo->prepare(
            'INSERT INTO rts_line_items (case_id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $insertedIds = [];
        foreach ($lines as $idx => $L) {
            $parentId = null;
            if ($L['is_bonus'] && isset($L['_bonus_parent_idx'])) {
                $parentId = $insertedIds[(int) $L['_bonus_parent_idx']] ?? null;
            }
            $li->execute([
                $caseId,
                $L['product_type'],
                $L['honey_size'],
                $L['honey_variant'],
                $L['combo_product'],
                $L['combo_quantity'],
                $L['combo_honey_size'],
                $L['combo_honey_variant'],
                $L['quantity'],
                $L['is_bonus'],
                $parentId,
            ]);
            $insertedIds[$idx] = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE rts_cases SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$caseId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Append failed']);
        exit;
    }
    echo json_encode(['ok' => true, 'case_id' => $caseId]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown action']);

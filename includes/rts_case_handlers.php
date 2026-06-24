<?php

declare(strict_types=1);

require_once __DIR__ . '/courier_detect.php';
require_once __DIR__ . '/rts_attachments.php';
require_once __DIR__ . '/rts_shop_couriers.php';
require_once __DIR__ . '/rts_claim_status.php';
require_once __DIR__ . '/rts_products.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/rts_storage.php';
require_once __DIR__ . '/rts_prefill.php';

class RtsCaseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'error',
        public readonly int $httpStatus = 400,
        public readonly ?array $payload = null,
    ) {
        parent::__construct($message);
    }
}

/** @return list<string> */
function rts_case_allowed_couriers(): array
{
    return ['spx', 'jnt', 'flash', 'unknown'];
}

/** @return list<string> */
function rts_case_allowed_shops(): array
{
    return ['shopee', 'lazada', 'tiktok', 'vip'];
}

function rts_case_resolve_disposition(array $data): string
{
    if (isset($data['case_disposition'])) {
        $d = trim((string) $data['case_disposition']);
        if (in_array($d, rts_case_allowed_dispositions(), true)) {
            return $d;
        }
        throw new RtsCaseException('Invalid case_disposition', 'invalid_disposition', 400);
    }
    $damagedReason = isset($data['damaged_reason']) ? trim((string) $data['damaged_reason']) : '';
    if ($damagedReason === 'leak') {
        return 'leak';
    }
    if (in_array($damagedReason, ['item_replaced', 'packaging_replaced'], true)) {
        return 'swapped';
    }

    return 'standard';
}

function rts_case_disposition_to_damaged_reason(string $disposition): ?string
{
    return match ($disposition) {
        'leak' => 'leak',
        'swapped' => 'item_replaced',
        default => null,
    };
}

/**
 * Registry list filter: match case_disposition when present, else legacy damaged_reason.
 *
 * @return array{sql: string, params: list<mixed>}
 */
function rts_case_disposition_registry_where(PDO $pdo, string $disposition): array
{
    if ($disposition === 'swapped') {
        $legacy = "c.damaged_reason IN ('item_replaced', 'packaging_replaced')";
        if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {
            return ['sql' => "(c.case_disposition = 'swapped' OR {$legacy})", 'params' => []];
        }

        return ['sql' => $legacy, 'params' => []];
    }
    if ($disposition === 'leak') {
        $legacy = "c.damaged_reason = 'leak'";
        if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {
            return ['sql' => "(c.case_disposition = 'leak' OR {$legacy})", 'params' => []];
        }

        return ['sql' => $legacy, 'params' => []];
    }
    throw new InvalidArgumentException('Registry filter supports swapped or leak only');
}

/**
 * @return array{available: bool, item_id: ?int, delivery_date: ?string, photo_filename: ?string, download_url: ?string}
 */
function rts_case_enrich_before_shipment(PDO $pdo, array $caseRow): array
{
    $fn = isset($caseRow['before_shipment_photo_filename'])
        ? trim((string) $caseRow['before_shipment_photo_filename'])
        : '';
    $itemId = isset($caseRow['delivery_match_item_id']) ? (int) $caseRow['delivery_match_item_id'] : 0;
    $deliveryDate = null;

    if ($fn === '' && $itemId > 0) {
        $st = $pdo->prepare(
            'SELECT i.photo_filename, b.delivery_date
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.id = ? LIMIT 1'
        );
        $st->execute([$itemId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $fn = trim((string) ($row['photo_filename'] ?? ''));
            $deliveryDate = (string) ($row['delivery_date'] ?? '');
        }
    }

    if ($fn === '' && isset($caseRow['tracking_number'])) {
        $match = rts_prefill_delivery_lookup($pdo, (string) $caseRow['tracking_number']);
        if ($match['match'] && trim((string) ($match['photo_filename'] ?? '')) !== '') {
            $fn = (string) $match['photo_filename'];
            $itemId = (int) ($match['item_id'] ?? 0);
            $deliveryDate = (string) ($match['delivery_date'] ?? '');
        }
    }

    if ($fn === '') {
        return ['available' => false, 'item_id' => null, 'delivery_date' => null, 'photo_filename' => null, 'download_url' => null];
    }

    return [
        'available' => true,
        'item_id' => $itemId > 0 ? $itemId : null,
        'delivery_date' => $deliveryDate !== '' && $deliveryDate !== '0' ? $deliveryDate : null,
        'photo_filename' => $fn,
        'download_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn),
    ];
}

/**
 * @return array{exists: bool, detected_courier: string, case: ?array, lines: list<array>}
 */
function rts_case_handler_get_by_tracking(PDO $pdo, string $tracking): array
{
    $tracking = trim($tracking);
    if ($tracking === '') {
        throw new RtsCaseException('Missing tracking', 'missing_tracking', 400);
    }
    $case = rts_cases_fetch_by_tracking($pdo, $tracking);
    if (!$case) {
        return [
            'exists' => false,
            'detected_courier' => rts_detect_courier($tracking),
            'case' => null,
            'lines' => [],
        ];
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
    if (!isset($case['case_disposition']) && rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {
        $case['case_disposition'] = rts_case_resolve_disposition(['damaged_reason' => $case['damaged_reason'] ?? '']);
    }
    $case['before_shipment'] = rts_case_enrich_before_shipment($pdo, $case);
    $lineRows = rts_line_items_fetch_for_case($pdo, $lid);

    return [
        'exists' => true,
        'detected_courier' => rts_detect_courier($tracking),
        'case' => $case,
        'lines' => $lineRows,
    ];
}

function rts_case_handler_find_delivery_match_item_id(PDO $pdo, string $tracking): ?int
{
    $st = $pdo->prepare(
        'SELECT i.id FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL
           AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
           AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
         ORDER BY b.submitted_at DESC, i.id DESC
         LIMIT 1'
    );
    $st->execute([$tracking]);
    $mid = $st->fetchColumn();
    if ($mid === false || (int) $mid < 1) {
        return null;
    }

    return (int) $mid;
}

/**
 * @return array{match: bool, item_id: ?int, photo_filename: ?string, delivery_date: ?string, file_url: ?string}
 */
function rts_case_handler_delivery_match(PDO $pdo, string $tracking): array
{
    $tracking = trim($tracking);
    if ($tracking === '') {
        throw new RtsCaseException('Missing tracking', 'missing_tracking', 400);
    }
    $st = $pdo->prepare(
        'SELECT i.id AS item_id, i.photo_filename, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL
           AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
           AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
         ORDER BY b.submitted_at DESC, i.id DESC
         LIMIT 1'
    );
    $st->execute([$tracking]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            'match' => false,
            'item_id' => null,
            'photo_filename' => null,
            'delivery_date' => null,
            'file_url' => null,
        ];
    }
    $fn = (string) $row['photo_filename'];

    return [
        'match' => true,
        'item_id' => (int) $row['item_id'],
        'photo_filename' => $fn,
        'delivery_date' => (string) $row['delivery_date'],
        'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn),
    ];
}

/**
 * @param array<string, mixed> $data
 * @return list<array<string, mixed>>
 */
function rts_case_parse_product_lines(string $disposition, array $data): array
{
    if ($disposition === 'swapped') {
        return [];
    }

    if ($disposition === 'leak') {
        $reusableIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
        $damagedIn = isset($data['damaged_lines']) && is_array($data['damaged_lines']) ? $data['damaged_lines'] : [];
        if ($reusableIn !== []) {
            $err = rts_validate_lines_v2($reusableIn);
            if ($err !== null) {
                throw new RtsCaseException($err, 'invalid_lines', 400);
            }
        }
        if ($damagedIn !== []) {
            $err = rts_validate_lines_v2($damagedIn);
            if ($err !== null) {
                throw new RtsCaseException($err, 'invalid_damaged_lines', 400);
            }
        }
        if ($reusableIn === [] && $damagedIn === []) {
            throw new RtsCaseException(
                'Leak cases require at least one damaged or reusable product line.',
                'leak_lines_required',
                400
            );
        }
        $lines = [];
        foreach (array_map('rts_normalize_line', $reusableIn) as $L) {
            $L['line_role'] = 'reusable';
            $lines[] = $L;
        }
        foreach (array_map('rts_normalize_line', $damagedIn) as $L) {
            $L['line_role'] = 'damaged';
            $lines[] = $L;
        }

        return $lines;
    }

    $linesIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
    $err = rts_validate_lines_v2($linesIn);
    if ($err !== null) {
        throw new RtsCaseException($err, 'invalid_lines', 400);
    }
    if ($linesIn === []) {
        throw new RtsCaseException('At least one product line is required.', 'lines_required', 400);
    }
    $lines = [];
    foreach (array_map('rts_normalize_line', $linesIn) as $L) {
        $L['line_role'] = 'reusable';
        $lines[] = $L;
    }

    return $lines;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function rts_case_handler_parse_case_fields(PDO $pdo, array $data, bool $isCreate): array
{
    $tracking = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';
    if ($tracking === '') {
        throw new RtsCaseException('Missing tracking_number', 'missing_tracking', 400);
    }

    $disposition = rts_case_resolve_disposition($data);
    $lines = rts_case_parse_product_lines($disposition, $data);

    $courierFinal = isset($data['courier_final']) ? (string) $data['courier_final'] : '';
    if ($courierFinal === '' || $courierFinal === 'unknown') {
        throw new RtsCaseException('courier_final is required', 'invalid_courier', 400);
    }
    if (!in_array($courierFinal, rts_case_allowed_couriers(), true)) {
        throw new RtsCaseException('Invalid courier_final', 'invalid_courier', 400);
    }

    $shop = isset($data['shop']) ? (string) $data['shop'] : '';
    if (!in_array($shop, rts_case_allowed_shops(), true)) {
        throw new RtsCaseException('Invalid shop', 'invalid_shop', 400);
    }
    if (!rts_shop_allows_courier($shop, $courierFinal)) {
        throw new RtsCaseException('Courier not allowed for this shop', 'invalid_courier', 400);
    }

    $vip = null;
    if ($shop === 'vip') {
        $vs = isset($data['vip_subsource']) ? (string) $data['vip_subsource'] : '';
        if ($vs !== '' && !in_array($vs, ['website', 'facebook'], true)) {
            throw new RtsCaseException('Invalid vip_subsource', 'invalid_vip', 400);
        }
        $vip = $vs === '' ? null : $vs;
    }

    $dd = isset($data['date_delivered']) ? trim((string) $data['date_delivered']) : '';
    $do = isset($data['date_opened']) ? trim((string) $data['date_opened']) : '';
    if ($dd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        throw new RtsCaseException('date_delivered is required', 'invalid_date', 400);
    }
    if ($do === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $do)) {
        throw new RtsCaseException('date_opened is required', 'invalid_date', 400);
    }

    $docPhoto = isset($data['documentation_photo']) ? trim((string) $data['documentation_photo']) : '';
    $normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);
    if ($normAtt['error'] !== null) {
        throw new RtsCaseException((string) $normAtt['error'], 'invalid_attachments', 400);
    }
    $attachList = $normAtt['list'];
    if ($attachList === [] && $docPhoto !== '') {
        if (!rts_attachment_filename_valid($docPhoto)) {
            throw new RtsCaseException('Invalid documentation_photo filename', 'invalid_attachments', 400);
        }
        $attachList[] = ['kind' => 'image', 'file' => $docPhoto];
    }
    if ($attachList === []) {
        throw new RtsCaseException(
            'At least one RTS documentation photo or video is required.',
            'documentation_required',
            400,
            ['message' => 'At least one RTS documentation photo or video is required.']
        );
    }

    $hasImage = false;
    $hasVideo = false;
    foreach ($attachList as $att) {
        if (($att['kind'] ?? '') === 'image') {
            $hasImage = true;
        }
        if (($att['kind'] ?? '') === 'video') {
            $hasVideo = true;
        }
    }
    if ($disposition === 'swapped') {
        if (!$hasImage) {
            throw new RtsCaseException(
                'Swapped parcel cases require at least one photo.',
                'swapped_requires_photo',
                400
            );
        }
        if (!$hasVideo) {
            throw new RtsCaseException(
                'Swapped parcel cases require at least one video.',
                'swapped_requires_video',
                400
            );
        }
    }

    $damagedReason = rts_case_disposition_to_damaged_reason($disposition);

    $deliveryMatchItemId = null;
    $beforeShipmentPhoto = null;
    if (in_array($disposition, ['swapped', 'leak'], true)) {
        $deliveryMatchItemId = rts_case_handler_find_delivery_match_item_id($pdo, $tracking);
        $deliveryLookup = rts_prefill_delivery_lookup($pdo, $tracking);
        if ($deliveryLookup['match'] && trim((string) ($deliveryLookup['photo_filename'] ?? '')) !== '') {
            $beforeShipmentPhoto = (string) $deliveryLookup['photo_filename'];
            if ($deliveryMatchItemId === null && !empty($deliveryLookup['item_id'])) {
                $deliveryMatchItemId = (int) $deliveryLookup['item_id'];
            }
        }
    }

    return [
        'tracking_number' => $tracking,
        'lines' => $lines,
        'courier_final' => $courierFinal,
        'shop' => $shop,
        'vip_subsource' => $vip,
        'date_delivered' => $dd,
        'date_opened' => $do,
        'documentation_photo' => rts_attachments_first_image($attachList),
        'documentation_attachments' => json_encode($attachList, JSON_UNESCAPED_UNICODE),
        'case_disposition' => $disposition,
        'damaged_reason' => $damagedReason,
        'damaged_tag' => rts_damaged_tag($damagedReason),
        'courier_detected' => rts_detect_courier($tracking),
        'delivery_match_item_id' => $deliveryMatchItemId,
        'before_shipment_photo_filename' => $beforeShipmentPhoto,
    ];
}

/** @return array{case_id: int} */
function rts_case_handler_create(PDO $pdo, array $data): array
{
    $fields = rts_case_handler_parse_case_fields($pdo, $data, true);
    $tracking = $fields['tracking_number'];
    $pdo->beginTransaction();
    try {
        if (rts_cases_find_active_id_by_tracking($pdo, $tracking) !== null) {
            $pdo->rollBack();
            throw new RtsCaseException(
                'This tracking number already exists.',
                'duplicate_tracking',
                409,
                ['message' => 'This tracking number already exists.']
            );
        }
        $caseId = rts_cases_insert_row($pdo, [
            'tracking_number' => $tracking,
            'courier_detected' => $fields['courier_detected'],
            'courier_final' => $fields['courier_final'],
            'shop' => $fields['shop'],
            'vip_subsource' => $fields['vip_subsource'],
            'date_delivered' => $fields['date_delivered'],
            'date_opened' => $fields['date_opened'],
            'documentation_photo' => $fields['documentation_photo'],
            'documentation_attachments' => $fields['documentation_attachments'],
            'case_disposition' => $fields['case_disposition'],
            'damaged_reason' => $fields['damaged_reason'],
            'damaged_tag' => $fields['damaged_tag'],
            'delivery_match_item_id' => $fields['delivery_match_item_id'],
            'before_shipment_photo_filename' => $fields['before_shipment_photo_filename'],
        ]);
        rts_line_items_insert_batch($pdo, $caseId, $fields['lines']);
        $pdo->commit();
    } catch (RtsCaseException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        throw new RtsCaseException($e->getMessage(), 'save_failed', 500);
    } catch (Throwable) {
        $pdo->rollBack();
        throw new RtsCaseException('Database error while saving.', 'save_failed', 500);
    }

    return ['case_id' => $caseId];
}

/** @return array{case_id: int} */
function rts_case_handler_update(PDO $pdo, int $caseId, array $data): array
{
    if ($caseId < 1) {
        throw new RtsCaseException('Invalid case_id', 'invalid_case_id', 400);
    }
    $fields = rts_case_handler_parse_case_fields($pdo, $data, false);
    $tracking = $fields['tracking_number'];
    $pdo->beginTransaction();
    try {
        $void = rts_cases_void_sql($pdo);
        $st = $pdo->prepare("SELECT id FROM rts_cases WHERE id = ? AND {$void} LIMIT 1");
        $st->execute([$caseId]);
        if (!$st->fetch()) {
            $pdo->rollBack();
            throw new RtsCaseException('Case not found or voided', 'not_found', 404);
        }
        $dupVoid = rts_cases_void_sql($pdo);
        $dup = $pdo->prepare(
            "SELECT id FROM rts_cases WHERE tracking_number = ? AND {$dupVoid} AND id != ? LIMIT 1"
        );
        $dup->execute([$tracking, $caseId]);
        if ($dup->fetch()) {
            $pdo->rollBack();
            throw new RtsCaseException(
                'Another record already uses this tracking number.',
                'duplicate_tracking',
                409,
                ['message' => 'Another record already uses this tracking number.']
            );
        }
        rts_cases_update_row($pdo, $caseId, [
            'tracking_number' => $tracking,
            'courier_detected' => $fields['courier_detected'],
            'courier_final' => $fields['courier_final'],
            'shop' => $fields['shop'],
            'vip_subsource' => $fields['vip_subsource'],
            'date_delivered' => $fields['date_delivered'],
            'date_opened' => $fields['date_opened'],
            'documentation_photo' => $fields['documentation_photo'],
            'documentation_attachments' => $fields['documentation_attachments'],
            'case_disposition' => $fields['case_disposition'],
            'damaged_reason' => $fields['damaged_reason'],
            'damaged_tag' => $fields['damaged_tag'],
            'delivery_match_item_id' => $fields['delivery_match_item_id'],
            'before_shipment_photo_filename' => $fields['before_shipment_photo_filename'],
        ]);
        $pdo->prepare('DELETE FROM rts_line_items WHERE case_id = ?')->execute([$caseId]);
        rts_line_items_insert_batch($pdo, $caseId, $fields['lines']);
        $pdo->commit();
    } catch (RtsCaseException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        throw new RtsCaseException($e->getMessage(), 'save_failed', 500);
    } catch (Throwable) {
        $pdo->rollBack();
        throw new RtsCaseException('Update failed', 'save_failed', 500);
    }

    return ['case_id' => $caseId];
}

/** @return array{case_id: int} */
function rts_case_handler_append_lines(PDO $pdo, string $tracking, array $data): array
{
    $tracking = trim($tracking);
    if ($tracking === '') {
        throw new RtsCaseException('Missing tracking_number', 'missing_tracking', 400);
    }
    $linesIn = isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : [];
    $err = rts_validate_lines_v2($linesIn);
    if ($err !== null) {
        throw new RtsCaseException($err, 'invalid_lines', 400);
    }
    $lines = array_map(static function (array $L): array {
        $L['line_role'] = 'reusable';

        return $L;
    }, array_map('rts_normalize_line', $linesIn));
    $caseId = rts_cases_find_active_id_by_tracking($pdo, $tracking);
    if ($caseId === null) {
        throw new RtsCaseException('Case not found, voided, or invalid tracking number', 'not_found', 404);
    }
    if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {
        $dispSt = $pdo->prepare('SELECT case_disposition, damaged_reason FROM rts_cases WHERE id = ? LIMIT 1');
        $dispSt->execute([$caseId]);
        $dispRow = $dispSt->fetch(PDO::FETCH_ASSOC) ?: [];
        $disp = (string) ($dispRow['case_disposition'] ?? 'standard');
        if ($disp === 'swapped') {
            throw new RtsCaseException('Cannot append product lines to a swapped parcel case.', 'swapped_no_lines', 400);
        }
    } else {
        $reasonSt = $pdo->prepare('SELECT damaged_reason FROM rts_cases WHERE id = ? LIMIT 1');
        $reasonSt->execute([$caseId]);
        $reason = (string) ($reasonSt->fetchColumn() ?: '');
        if (in_array($reason, ['item_replaced', 'packaging_replaced'], true)) {
            throw new RtsCaseException('Cannot append product lines to a swapped parcel case.', 'swapped_no_lines', 400);
        }
    }
    $pdo->beginTransaction();
    try {
        rts_line_items_insert_batch($pdo, $caseId, $lines);
        if (rts_db_has_column($pdo, 'rts_cases', 'updated_at')) {
            $pdo->prepare('UPDATE rts_cases SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$caseId]);
        }
        $pdo->commit();
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        throw new RtsCaseException($e->getMessage(), 'save_failed', 500);
    } catch (Throwable) {
        $pdo->rollBack();
        throw new RtsCaseException('Append failed', 'save_failed', 500);
    }

    return ['case_id' => $caseId];
}

function rts_case_handler_void(PDO $pdo, int $caseId, int $userId): void
{
    if ($caseId < 1) {
        throw new RtsCaseException('Invalid case_id', 'invalid_case_id', 400);
    }
    $st = $pdo->prepare(
        'UPDATE rts_cases SET voided_at = NOW(), voided_by_user_id = ? WHERE id = ? AND voided_at IS NULL'
    );
    $st->execute([$userId, $caseId]);
    if ($st->rowCount() === 0) {
        throw new RtsCaseException('Case not found or already voided', 'not_found', 404);
    }
}

/** @return array{claim_status: string, claim_status_label: string, claim_status_note: string} */
function rts_case_handler_update_claim(PDO $pdo, int $caseId, string $claimStatus, string $claimNote): array
{
    if ($caseId < 1) {
        throw new RtsCaseException('Invalid case_id', 'invalid_case_id', 400);
    }
    if (!rts_claim_status_valid($claimStatus)) {
        throw new RtsCaseException('Invalid claim status', 'invalid_claim', 400);
    }
    if (strlen($claimNote) > 512) {
        $claimNote = substr($claimNote, 0, 512);
    }
    $st = $pdo->prepare('SELECT id FROM rts_cases WHERE id = ? LIMIT 1');
    $st->execute([$caseId]);
    if (!$st->fetch()) {
        throw new RtsCaseException('Case not found', 'not_found', 404);
    }
    $upd = $pdo->prepare('UPDATE rts_cases SET claim_status = ?, claim_status_note = ? WHERE id = ?');
    $upd->execute([$claimStatus, $claimNote, $caseId]);

    return [
        'claim_status' => $claimStatus,
        'claim_status_label' => rts_claim_status_label($claimStatus),
        'claim_status_note' => $claimNote,
    ];
}

/**
 * @param array<string, mixed> $filters
 * @return array{cases: list<array>, total: int, page: int, per_page: int, total_pages: int}
 */
function rts_case_handler_list(PDO $pdo, array $filters): array
{
    $q = trim((string) ($filters['q'] ?? ''));
    $shop = trim((string) ($filters['shop'] ?? ''));
    $courier = trim((string) ($filters['courier'] ?? ''));
    $vipOnly = !empty($filters['vip_only']);
    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    $delFrom = trim((string) ($filters['del_from'] ?? ''));
    $delTo = trim((string) ($filters['del_to'] ?? ''));
    $damage = trim((string) ($filters['damage'] ?? ''));
    $voidOnly = !empty($filters['void_only']);
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = 'c.tracking_number LIKE ?';
        $params[] = '%' . $q . '%';
    }
    if ($vipOnly) {
        $where[] = "c.shop = 'vip'";
    } elseif ($shop !== '' && in_array($shop, rts_case_allowed_shops(), true)) {
        $where[] = 'c.shop = ?';
        $params[] = $shop;
    }
    if ($courier !== '' && in_array($courier, rts_case_allowed_couriers(), true)) {
        $where[] = 'c.courier_final = ?';
        $params[] = $courier;
    }
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'c.date_opened >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'c.date_opened <= ?';
        $params[] = $dateTo;
    }
    if ($delFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delFrom)) {
        $where[] = 'c.date_delivered >= ?';
        $params[] = $delFrom;
    }
    if ($delTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delTo)) {
        $where[] = 'c.date_delivered <= ?';
        $params[] = $delTo;
    }
    if ($damage === 'all_damaged') {
        $where[] = 'c.damaged_reason IS NOT NULL';
    } elseif ($damage === 'not_damaged') {
        $where[] = 'c.damaged_reason IS NULL';
    } elseif (in_array($damage, ['leak', 'item_replaced', 'packaging_replaced'], true)) {
        $where[] = 'c.damaged_reason = ?';
        $params[] = $damage;
    } elseif (rts_db_has_column($pdo, 'rts_cases', 'case_disposition') && in_array($damage, ['standard', 'swapped', 'leak'], true)) {
        $where[] = 'c.case_disposition = ?';
        $params[] = $damage;
    }
    if ($voidOnly) {
        $where[] = 'c.voided_at IS NOT NULL';
    }
    $whereSql = implode(' AND ', $where);
    $orderSql = $voidOnly
        ? 'ORDER BY c.voided_at DESC, c.id DESC'
        : 'ORDER BY (c.voided_at IS NOT NULL) ASC, (c.damaged_reason IS NOT NULL) DESC, c.date_opened DESC, c.id DESC';

    $cntSt = $pdo->prepare('SELECT COUNT(*) FROM rts_cases c WHERE ' . $whereSql);
    $cntSt->execute($params);
    $total = (int) $cntSt->fetchColumn();

    $hasDisposition = rts_db_has_column($pdo, 'rts_cases', 'case_disposition');
    $dispositionCol = $hasDisposition ? ', c.case_disposition' : '';
    $hasBeforeShip = rts_db_has_column($pdo, 'rts_cases', 'before_shipment_photo_filename');
    $beforeShipCol = $hasBeforeShip ? ', c.before_shipment_photo_filename' : '';

    $sql = 'SELECT c.id, c.tracking_number, c.courier_detected, c.courier_final, c.shop, c.vip_subsource,
         c.date_delivered, c.date_opened, c.documentation_photo, c.documentation_attachments,
         c.damaged_reason, c.damaged_tag, c.claim_status, c.claim_status_note,
         c.voided_at, c.created_at, c.delivery_match_item_id' . $dispositionCol . $beforeShipCol . ',
         (SELECT COUNT(*) FROM rts_line_items li WHERE li.case_id = c.id) AS line_count
         FROM rts_cases c WHERE ' . $whereSql . ' ' . $orderSql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $cases = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cases as &$row) {
        $attRaw = $row['documentation_attachments'] ?? null;
        unset($row['documentation_attachments']);
        $row['documentation_attachments'] = rts_attachments_decode(
            $attRaw !== null && $attRaw !== '' ? (string) $attRaw : null,
            isset($row['documentation_photo']) ? (string) $row['documentation_photo'] : null
        );
        if (!isset($row['case_disposition'])) {
            $row['case_disposition'] = rts_case_resolve_disposition(['damaged_reason' => $row['damaged_reason'] ?? '']);
        }
        $row['before_shipment'] = rts_case_enrich_before_shipment($pdo, $row);
        unset($row['before_shipment_photo_filename']);
    }
    unset($row);

    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

    return [
        'cases' => $cases,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * @param array<string, mixed> $filters
 * @return array{cases: list<array>, total: int, page: int, per_page: int, total_pages: int}
 */
function rts_case_handler_list_by_disposition(PDO $pdo, string $disposition, array $filters): array
{
    $filters['disposition'] = $disposition;
    $q = trim((string) ($filters['q'] ?? ''));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));
    $offset = ($page - 1) * $perPage;

    $registry = rts_case_disposition_registry_where($pdo, $disposition);
    $where = [$registry['sql'], 'c.voided_at IS NULL'];
    $params = $registry['params'];
    if ($q !== '') {
        $where[] = 'c.tracking_number LIKE ?';
        $params[] = '%' . $q . '%';
    }
    $whereSql = implode(' AND ', $where);

    $cntSt = $pdo->prepare('SELECT COUNT(*) FROM rts_cases c WHERE ' . $whereSql);
    $cntSt->execute($params);
    $total = (int) $cntSt->fetchColumn();

    $sql = 'SELECT c.id, c.tracking_number, c.shop, c.courier_final, c.date_opened, c.date_delivered,
         c.documentation_photo, c.documentation_attachments, c.delivery_match_item_id,
         c.before_shipment_photo_filename, c.case_disposition
         FROM rts_cases c WHERE ' . $whereSql . ' ORDER BY c.date_opened DESC, c.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $cases = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($cases as &$row) {
        $attRaw = $row['documentation_attachments'] ?? null;
        unset($row['documentation_attachments']);
        $row['documentation_attachments'] = rts_attachments_decode(
            $attRaw !== null && $attRaw !== '' ? (string) $attRaw : null,
            isset($row['documentation_photo']) ? (string) $row['documentation_photo'] : null
        );
        $row['before_shipment'] = rts_case_enrich_before_shipment($pdo, $row);
        if ($disposition === 'leak') {
            $row['lines'] = rts_line_items_fetch_for_case($pdo, (int) $row['id']);
        }
        unset($row['before_shipment_photo_filename']);
    }
    unset($row);

    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

    return [
        'cases' => $cases,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

function rts_case_handler_list_swapped(PDO $pdo, array $filters): array
{
    return rts_case_handler_list_by_disposition($pdo, 'swapped', $filters);
}

function rts_case_handler_list_leaks(PDO $pdo, array $filters): array
{
    return rts_case_handler_list_by_disposition($pdo, 'leak', $filters);
}

/** @return array<string, mixed> */
function rts_case_handler_tracking_prefill_api(PDO $pdo, string $tracking): array
{
    try {
        return rts_case_handler_tracking_prefill($pdo, $tracking);
    } catch (RuntimeException $e) {
        throw new RtsCaseException($e->getMessage(), 'missing_tracking', 400);
    }
}

/** @return array<string, mixed> */
function rts_case_handler_dashboard(PDO $pdo): array
{
    $totalCases = (int) $pdo->query('SELECT COUNT(*) FROM rts_cases')->fetchColumn();
    $casesLast30 = (int) $pdo->query(
        'SELECT COUNT(*) FROM rts_cases WHERE date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'
    )->fetchColumn();
    $casesThisMonth = (int) $pdo->query(
        'SELECT COUNT(*) FROM rts_cases WHERE date_opened >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
         AND date_opened < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), "%Y-%m-01")'
    )->fetchColumn();
    $vipCount = (int) $pdo->query("SELECT COUNT(*) FROM rts_cases WHERE shop = 'vip'")->fetchColumn();
    $vipPct = $totalCases > 0 ? round(100 * $vipCount / $totalCases, 1) : 0.0;
    $byShop = $pdo->query('SELECT shop, COUNT(*) AS cnt FROM rts_cases GROUP BY shop ORDER BY cnt DESC')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    $byCourier = $pdo->query('SELECT courier_final, COUNT(*) AS cnt FROM rts_cases GROUP BY courier_final ORDER BY cnt DESC')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    $recent = $pdo->query(
        'SELECT id, tracking_number, shop, courier_final, date_opened, damaged_tag, voided_at
         FROM rts_cases ORDER BY date_opened DESC, id DESC LIMIT 15'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $timeline = $pdo->query(
        'SELECT DATE_FORMAT(date_opened, "%Y-%m-%d") AS d, COUNT(*) AS cnt
         FROM rts_cases WHERE date_opened >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         GROUP BY d ORDER BY d ASC'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $swappedLast30 = 0;
    $leaksLast30 = 0;
    if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {
        $swappedLast30 = (int) $pdo->query(
            "SELECT COUNT(*) FROM rts_cases c WHERE c.voided_at IS NULL
             AND (c.case_disposition = 'swapped' OR c.damaged_reason IN ('item_replaced', 'packaging_replaced'))
             AND c.date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
        $leaksLast30 = (int) $pdo->query(
            "SELECT COUNT(*) FROM rts_cases c WHERE c.voided_at IS NULL
             AND (c.case_disposition = 'leak' OR c.damaged_reason = 'leak')
             AND c.date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
    } else {
        $swappedLast30 = (int) $pdo->query(
            "SELECT COUNT(*) FROM rts_cases c WHERE c.voided_at IS NULL
             AND c.damaged_reason IN ('item_replaced', 'packaging_replaced')
             AND c.date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
        $leaksLast30 = (int) $pdo->query(
            "SELECT COUNT(*) FROM rts_cases c WHERE c.voided_at IS NULL
             AND c.damaged_reason = 'leak'
             AND c.date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
    }

    return [
        'total_cases' => $totalCases,
        'cases_last_30' => $casesLast30,
        'cases_this_month' => $casesThisMonth,
        'swapped_last_30' => $swappedLast30,
        'leaks_last_30' => $leaksLast30,
        'vip_count' => $vipCount,
        'vip_pct' => $vipPct,
        'by_shop' => $byShop,
        'by_courier' => $byCourier,
        'recent' => $recent,
        'timeline' => $timeline,
    ];
}

/** @return array<string, mixed> */
function rts_case_handler_product_catalog(): array
{
    return [
        'product_types' => RTS_PRODUCT_TYPES,
        'product_labels' => RTS_PRODUCT_LABELS,
        'honey_sizes' => RTS_HONEY_SIZES,
        'honey_size_labels' => RTS_HONEY_SIZE_LABELS,
        'honey_variants' => RTS_HONEY_VARIANTS,
        'honey_variant_labels' => RTS_HONEY_VARIANT_LABELS,
        'combo_products' => RTS_COMBO_PRODUCTS,
        'combo_product_labels' => RTS_COMBO_PRODUCT_LABELS,
        'shop_couriers' => rts_shop_courier_map(),
        'shops' => rts_case_allowed_shops(),
        'couriers' => ['jnt' => 'J&T', 'spx' => 'SPX', 'flash' => 'Flash'],
        'claim_statuses' => rts_claim_status_options(),
        'dispositions' => [
            ['id' => 'standard', 'label' => 'Standard return'],
            ['id' => 'swapped', 'label' => 'Swapped parcel'],
            ['id' => 'leak', 'label' => 'Leaked / damaged'],
        ],
        'damaged_reasons' => [
            ['id' => 'leak', 'label' => 'Leak'],
            ['id' => 'item_replaced', 'label' => 'Item replaced'],
            ['id' => 'packaging_replaced', 'label' => 'Packaging replaced'],
        ],
        'doc_url_base' => rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''),
        'attachments_max' => 24,
    ];
}

/**
 * @param array<string, mixed>|null $file
 * @return array{file_name: string, file_url: string, media_kind: string}
 */
function rts_case_handler_upload_documentation(?array $file): array
{
    if ($file === null || !is_array($file)) {
        throw new RtsCaseException('Missing file input', 'missing_file', 400);
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RtsCaseException('Upload failed', 'upload_failed', 400);
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || $size < 1) {
        throw new RtsCaseException('Empty upload', 'upload_failed', 400);
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $imageMax = 12 * 1024 * 1024;
    $videoMax = 120 * 1024 * 1024;
    $kind = '';
    $ext = '';
    $maxForType = $imageMax;
    switch ($mime) {
        case 'image/jpeg':
            $ext = 'jpg';
            $kind = 'image';
            break;
        case 'image/png':
            $ext = 'png';
            $kind = 'image';
            break;
        case 'image/webp':
            $ext = 'webp';
            $kind = 'image';
            break;
        case 'image/gif':
            $ext = 'gif';
            $kind = 'image';
            break;
        case 'image/heic':
        case 'image/heif':
            $ext = 'heic';
            $kind = 'image';
            break;
        case 'video/mp4':
            $ext = 'mp4';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'video/webm':
            $ext = 'webm';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'video/quicktime':
            $ext = 'mov';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        default:
            throw new RtsCaseException('Unsupported type (use JPG/PNG/WebP/GIF or MP4/WebM/MOV)', 'unsupported_type', 415);
    }
    if ($size > $maxForType) {
        $mb = (int) round($maxForType / (1024 * 1024));
        throw new RtsCaseException('File too large (max about ' . $mb . 'MB for this type)', 'file_too_large', 413);
    }
    $targetDir = rts_storage_dir(RTS_STORAGE_BUCKET_RTS);
    $prefix = $kind === 'video' ? 'rtsvid_' : 'rtsdoc_';
    $name = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $name;
    if (!move_uploaded_file($tmp, $targetPath)) {
        throw new RtsCaseException('Could not save uploaded file', 'upload_failed', 500);
    }

    return [
        'file_name' => $name,
        'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_RTS, $name),
        'media_kind' => $kind,
    ];
}

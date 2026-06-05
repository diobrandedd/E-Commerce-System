<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rts_case_handlers.php';

header('Content-Type: application/json; charset=utf-8');

rts_session_start();
$authUser = rts_auth_user();
if (!rts_can_access_rts_admin($authUser)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

function rts_case_json_error(RtsCaseException $e): void
{
    http_response_code($e->httpStatus);
    $out = ['ok' => false, 'error' => $e->errorCode, 'message' => $e->getMessage()];
    if ($e->payload !== null) {
        $out = array_merge($out, $e->payload);
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $tracking = isset($_GET['tracking']) ? trim((string) $_GET['tracking']) : '';
    try {
        $pdo = rts_pdo();
        $result = rts_case_handler_get_by_tracking($pdo, $tracking);
        echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
    } catch (RtsCaseException $e) {
        rts_case_json_error($e);
    } catch (Throwable) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Lookup failed']);
    }
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
    if ($action === 'update_claim') {
        $result = rts_case_handler_update_claim(
            $pdo,
            (int) ($data['case_id'] ?? 0),
            trim((string) ($data['claim_status'] ?? '')),
            trim((string) ($data['claim_status_note'] ?? ''))
        );
        echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'void') {
        rts_case_handler_void($pdo, (int) ($data['case_id'] ?? 0), (int) $authUser['id']);
        echo json_encode(['ok' => true, 'message' => 'Record voided']);
        exit;
    }
    if ($action === 'update_record') {
        $result = rts_case_handler_update($pdo, (int) ($data['case_id'] ?? 0), $data);
        echo json_encode(['ok' => true] + $result);
        exit;
    }
    if ($action === 'create') {
        $result = rts_case_handler_create($pdo, $data);
        echo json_encode(['ok' => true] + $result);
        exit;
    }
    if ($action === 'append_lines') {
        $result = rts_case_handler_append_lines($pdo, $tracking, $data);
        echo json_encode(['ok' => true] + $result);
        exit;
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
} catch (RtsCaseException $e) {
    rts_case_json_error($e);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}

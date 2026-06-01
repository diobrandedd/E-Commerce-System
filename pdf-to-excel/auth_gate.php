<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/pdf_to_excel.php';

ims_session_start();

$user = ims_current_user();
if ($user === null || !waybill_stamper_user_can_access($user)) {
    header('Location: /rts/app/dist/', true, 302);
    exit;
}

/** Redirect standalone UI to the React SPA (processing uses api/v1/pdf_to_excel.php). */
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$isLegacyDownload = isset($_GET['download']);
$isLegacyProcess = $method === 'POST' && (($_POST['action'] ?? '') === 'process');
if ($method === 'GET' && !$isLegacyDownload) {
    header('Location: /rts/app/dist/#/waybill/pdf-to-excel', true, 302);
    exit;
}
if (!$isLegacyProcess && $method !== 'GET') {
    header('Location: /rts/app/dist/#/waybill/pdf-to-excel', true, 302);
    exit;
}

require __DIR__ . '/index.php';

<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/waybill_stamper.php';

ims_session_start();

$user = ims_current_user();
if ($user === null || !waybill_stamper_user_can_access($user)) {
    header('Location: /rts/app/dist/', true, 302);
    exit;
}

/** Redirect standalone HTML UI to the React SPA (processing uses api/v1/waybill.php). */
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$isLegacyDownload = isset($_GET['download']);
$isLegacyProcess = $method === 'POST' && (($_POST['action'] ?? '') === 'process');
if ($method === 'GET' && !$isLegacyDownload) {
    header('Location: /rts/app/dist/#/waybill-stamper', true, 302);
    exit;
}
if (!$isLegacyProcess && $method !== 'GET') {
    header('Location: /rts/app/dist/#/waybill-stamper', true, 302);
    exit;
}

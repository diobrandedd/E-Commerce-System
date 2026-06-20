<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/order_remarks.php';

ims_session_start();

$user = ims_current_user();
if ($user === null || !order_remarks_user_can_access($user)) {
    header('Location: /rts/app/dist/', true, 302);
    exit;
}

/** Redirect standalone UI to the React SPA. */
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET' && !isset($_GET['download'])) {
    header('Location: /rts/app/dist/#/waybill/order-remarks', true, 302);
    exit;
}

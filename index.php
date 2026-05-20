<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_check.php';

if (ims_pos_authenticated()) {
    header('Location: ' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);
    exit;
}
if (ims_current_user() !== null) {
    header('Location: ' . ims_redirect_dashboard_by_role((string) ims_current_user()['role']), true, 302);
    exit;
}
if (rts_auth_user() !== null) {
    header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);
    exit;
}

header('Location: login.php', true, 302);
exit;

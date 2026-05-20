<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (rts_auth_user() !== null) {
    header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);
    exit;
}

header('Location: login.php', true, 302);
exit;

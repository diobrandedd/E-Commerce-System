<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (rts_auth_user() !== null && rts_auth_user()['role'] === RTS_ROLE_ADMIN) {
    header('Location: dashboard.php', true, 302);
    exit;
}

header('Location: login.php', true, 302);
exit;

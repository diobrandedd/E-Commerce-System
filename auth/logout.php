<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

ims_logout();
header('Location: login.php', true, 302);
exit;

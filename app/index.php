<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/forwarding.php';
rts_forwarding_maybe_redirect();

$target = '/rts/app/dist/';
if (rts_forwarding_enabled() && rts_forwarding_public_url() !== '') {
    header('Location: ' . rts_forwarding_public_url() . $target, true, 302);
    exit;
}

header('Location: ' . $target, true, 302);
exit;

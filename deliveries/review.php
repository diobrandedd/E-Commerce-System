<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/forwarding.php';

header('Location: ../app/dist/deliveries/review', true, 302);
exit;

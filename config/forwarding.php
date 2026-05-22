<?php

/**
 * Active forwarding URL — edit when switching ngrok accounts (testing vs live).
 * See forwarding.sample.php for field descriptions.
 */
return [
    'enabled' => true,
    'public_url' => 'https://overlying-outskirts-sludge.ngrok-free.dev',
    'label' => 'testing',
    'allow_localhost_without_redirect' => true,
    'ngrok_skip_browser_warning' => true,
];

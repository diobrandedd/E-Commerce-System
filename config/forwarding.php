<?php

/**
 * Active public HTTPS URL for mobile camera / QR and external access.
 * Cloudflare Tunnel: pyxtracker.pyxfood.com → localhost:80 (Pyx_Tracker tunnel).
 * See forwarding.sample.php for field descriptions.
 */
return [
    'enabled' => true,
    'public_url' => 'https://pyxtracker.pyxfood.com',
    'label' => 'cloudflare-tunnel',
    'allow_localhost_without_redirect' => true,
    'ngrok_skip_browser_warning' => false,
];

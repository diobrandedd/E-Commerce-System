<?php

/**
 * Public HTTPS URL for mobile camera / QR and external access (ngrok).
 *
 * Copy this file to forwarding.php and edit when you switch accounts:
 *   - testing ngrok account
 *   - live / production ngrok account
 *
 * ngrok forwards to your XAMPP Apache (e.g. http://127.0.0.1:80).
 * Use only the public origin here — no /rts suffix. Paths are kept automatically.
 *
 * Example:
 *   'public_url' => 'https://your-subdomain.ngrok-free.dev',
 */
return [
    /** Set false to use LAN IP / localhost only (no HTTPS redirect). */
    'enabled' => true,

    /**
     * Full HTTPS base URL from ngrok (no trailing slash).
     * Switch this when you change ngrok accounts.
     */
    'public_url' => 'https://your-subdomain.ngrok-free.dev',

    /** Optional note for yourself: testing | live */
    'label' => 'testing',

    /**
     * When true, http://localhost and http://127.0.0.1 are NOT redirected
     * (handy for XAMPP dev on the server PC). LAN/mobile IPs still redirect.
     */
    'allow_localhost_without_redirect' => true,

    /** Send ngrok interstitial skip header on API fetch (ngrok free tier). */
    'ngrok_skip_browser_warning' => true,
];

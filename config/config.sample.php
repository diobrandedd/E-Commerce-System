<?php
/**
 * Copy to config.php and adjust for your server.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'rts_system',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    /**
     * Deprecated: honey AP sync uses HTTP.
     * Configure base URL + token in System Settings → PyxAcct integration
     * (must match PyxAcct config integration.tracker_honey_token).
     */
    // 'pyxacct_db' => [ ... ],

    /**
     * Upload storage OUTSIDE the RTS git folder (recommended).
     *
     * Create the folder (e.g. C:/xampp/htdocs/rts_storage) with subfolders:
     *   rts_documentation/
     *   deliveries_documentation/
     *   sp_sort/
     *   waybill_stamper/   (uploads, outputs, stash — CSR waybill stamper)
     *   pdf_to_excel/      (stash, outputs, work)
     *   order_remarks/     (stash, outputs)
     *   online_orders/     (outputs, work — master Excel merge)
     *
     * web_base: URL path from Apache document root (htdocs). Sibling folder
     *   htdocs/rts_storage → web_base '/rts_storage'
     *
     * Leave 'root' empty to store files inside the project at assets/* (old behavior).
     */
    'storage' => [
        'root' => 'C:/xampp/htdocs/rts_storage',
        'web_base' => '/rts_storage',
    ],

    // Mobile HTTPS / ngrok: see config/forwarding.sample.php (separate file).

    /**
     * Silent POS receipt printer (Windows). Exact name from Printers & scanners.
     * Uses GDI text via the driver (same path as Chrome print, no dialog).
     * Prefer the vendor queue that works with Chrome (e.g. POS-58 on USB).
     */
    'pos_thermal_printer' => 'POS-58',
    'pos_thermal_port' => '',
];

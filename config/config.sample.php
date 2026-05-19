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
     * Upload storage OUTSIDE the RTS git folder (recommended).
     *
     * Create the folder (e.g. C:/xampp/htdocs/rts_storage) with subfolders:
     *   rts_documentation/
     *   deliveries_documentation/
     *   sp_sort/
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
];

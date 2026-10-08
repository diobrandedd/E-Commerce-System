<?php
/**
 * Copy from sample if needed. Defaults match typical XAMPP MySQL.
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
    // Deprecated: honey AP auto-sync now uses HTTP (System Settings → PyxAcct URL + token).
    // Kept only so old installs do not break if something still reads the key.
    // 'pyxacct_db' => [ ... ],
    // Uploads outside this git folder — see config/config.sample.php and README.md
    'storage' => [
        'root' => 'C:/xampp/htdocs/rts_storage',
        'web_base' => '/rts_storage',
    ],
    // Exact Windows printer name for silent POS print (GDI via driver — same as Chrome).
    // Use vendor "POS-58" on USB. Avoid POS-58-RAW / copy-of LPT queues for this path.
    'pos_thermal_printer' => 'POS-58',
    'pos_thermal_port' => '',
];

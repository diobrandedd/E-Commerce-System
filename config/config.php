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
    // Uploads outside this git folder — see config/config.sample.php and README.md
    'storage' => [
        'root' => 'C:/xampp/htdocs/rts_storage',
        'web_base' => '/rts_storage',
    ],
    // Exact name from Windows Printers & scanners (silent ESC/POS print)
    'pos_thermal_printer' => 'POS-58(copy of 1)',
    'pos_thermal_port' => '',
];

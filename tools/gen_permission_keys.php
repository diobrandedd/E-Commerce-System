<?php

declare(strict_types=1);

/**
 * Generate app/src/app/lib/permissionKeys.ts from includes/permissions.php catalog.
 *
 * Usage: php tools/gen_permission_keys.php
 */

$root = dirname(__DIR__);
require_once $root . '/includes/permissions.php';

$catalog = ims_permission_catalog();
$keys = array_keys($catalog);
sort($keys);

$union = implode("\n  | ", array_map(static fn(string $k): string => json_encode($k, JSON_UNESCAPED_UNICODE), $keys));

$keyLines = implode("\n", array_map(
    static fn(string $k): string => '  ' . json_encode($k, JSON_UNESCAPED_UNICODE) . ',',
    $keys
));

$out = "/** Auto-generated from includes/permissions.php — run: php tools/gen_permission_keys.php */\n"
    . "export type PermissionKey =\n  | {$union};\n\n"
    . "export const PERMISSION_KEYS: readonly PermissionKey[] = [\n{$keyLines}\n] as const;\n";

$target = $root . '/app/src/app/lib/permissionKeys.ts';
file_put_contents($target, $out);
echo "Wrote {$target} (" . count($keys) . " keys)\n";

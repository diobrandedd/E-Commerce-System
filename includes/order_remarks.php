<?php

declare(strict_types=1);

/**
 * Order remarks — multi-carrier Excel read/write (column M or detected remarks column).
 */

require_once __DIR__ . '/waybill_stamper.php';

function order_remarks_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_ORDER_REMARKS,
        'order-remarks',
        ['stash', 'outputs'],
    );
}

function order_remarks_workbook_script(): string
{
    return __DIR__ . '/order_remarks_workbook.py';
}

/** @return array{id:int,role:string,dept_id:?int} */
function order_remarks_require_access(): array
{
    return waybill_stamper_require_access();
}

/** @param array{role:string,dept_id:?int} $user */
function order_remarks_user_can_access(array $user): bool
{
    return waybill_stamper_user_can_access($user);
}

/**
 * Courier format profiles (passed to Python as JSON).
 *
 * @return list<array<string, mixed>>
 */
function order_remarks_profiles(): array
{
    return [
        [
            'id' => 'jnt',
            'label' => 'J&T Express',
            'branding' => ['j&t', 'jnt', 'j and t', 'jet express'],
            'header_markers' => ['Receiver(*)', 'Receiver(*'],
            'receiver_header_aliases' => ['Receiver(*)'],
            'phone_header_aliases' => ['Receiver Telephone (*)', 'Receiver Telephone'],
            'address_header_aliases' => ['Receiver Address (*)', 'Receiver Address'],
            'province_header_aliases' => ['Receiver Province (*)', 'Receiver Province'],
            'city_header_aliases' => ['Receiver City (*)', 'Receiver City'],
            'region_header_aliases' => ['Receiver Region (*)', 'Receiver Region'],
            'remarks_header_aliases' => ['Remarks', 'Remark'],
            'skip_receiver_values' => [
                'Receiver(*)',
                '(*) Information that must be filled out',
            ],
            'default_columns' => [
                'receiver' => 0,
                'phone' => 1,
                'address' => 2,
                'province' => 3,
                'city' => 4,
                'region' => 5,
                'remarks' => 12,
            ],
        ],
        [
            'id' => 'spx',
            'label' => 'SPX / Shopee Express',
            'branding' => ['spx', 'shopee express', 'shopee logistics'],
            'header_markers' => [],
            'receiver_header_aliases' => [
                'Receiver Name',
                'Receiver',
                'Consignee',
                'Recipient',
                'Name',
            ],
            'remarks_header_aliases' => ['Remarks', 'Remark', 'Notes', 'Note', 'Buyer Message'],
            'address_header_aliases' => ['Address', 'Delivery Address', 'Street'],
            'city_header_aliases' => ['City', 'Town', 'Municipality'],
            'province_header_aliases' => ['Province', 'State'],
            'skip_receiver_values' => ['Receiver', 'Receiver Name', 'Consignee'],
            'default_columns' => ['receiver' => 0, 'phone' => 1, 'address' => 2, 'remarks' => 12],
        ],
        [
            'id' => 'flash',
            'label' => 'Flash Express',
            'branding' => ['flash express', 'flash'],
            'header_markers' => [],
            'receiver_header_aliases' => [
                'Receiver',
                'Recipient',
                'Consignee',
                'Receiver Name',
            ],
            'remarks_header_aliases' => ['Remarks', 'Remark', 'Notes', 'Note'],
            'address_header_aliases' => ['Address', 'Delivery Address'],
            'city_header_aliases' => ['City', 'Town'],
            'province_header_aliases' => ['Province'],
            'skip_receiver_values' => ['Receiver', 'Recipient'],
            'default_columns' => ['receiver' => 0, 'phone' => 1, 'address' => 2, 'remarks' => 12],
        ],
        [
            'id' => 'generic_remarks',
            'label' => 'Generic export',
            'branding' => [],
            'header_markers' => [],
            'receiver_header_aliases' => [
                'Receiver(*)',
                'Receiver',
                'Receiver Name',
                'Consignee',
                'Recipient',
            ],
            'remarks_header_aliases' => ['Remarks', 'Remark', 'Notes', 'Note', 'Buyer Message'],
            'address_header_aliases' => ['Address', 'Delivery Address', 'Street'],
            'city_header_aliases' => ['City', 'Town', 'Municipality', 'Barangay'],
            'province_header_aliases' => ['Province', 'State', 'Region'],
            'skip_receiver_values' => [
                'Receiver(*)',
                '(*) Information that must be filled out',
            ],
            'default_columns' => [
                'receiver' => 0,
                'phone' => 1,
                'address' => 2,
                'province' => 3,
                'city' => 4,
                'remarks' => 12,
            ],
        ],
    ];
}

/** @return list<array{id:string,label:string}> */
function order_remarks_profile_labels(): array
{
    $out = [];
    foreach (order_remarks_profiles() as $p) {
        $out[] = ['id' => (string) $p['id'], 'label' => (string) $p['label']];
    }
    return $out;
}

function order_remarks_profiles_json(): string
{
    $json = json_encode(order_remarks_profiles(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Could not encode carrier profiles.');
    }
    return $json;
}

/**
 * @return array{cmd:string, env:array<string,string>}
 */
function order_remarks_python_command(string ...$args): array
{
    $python = waybill_stamper_resolve_python();
    $script = order_remarks_workbook_script();
    if (!is_file($script)) {
        throw new RuntimeException('order_remarks_workbook.py is missing.');
    }

    $quoted = array_map(static fn (string $a): string => escapeshellarg($a), $args);
    $cmd = $python . ' ' . escapeshellarg($script) . ' ' . implode(' ', $quoted);

    $env = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $env['PYTHONIOENCODING'] = 'utf-8';
    }

    return ['cmd' => $cmd, 'env' => $env];
}

function order_remarks_write_temp_json(string $json): string
{
    $path = tempnam(sys_get_temp_dir(), 'orj_');
    if ($path === false) {
        throw new RuntimeException('Could not create temp file for Python.');
    }
    if (file_put_contents($path, $json) === false) {
        @unlink($path);
        throw new RuntimeException('Could not write temp JSON for Python.');
    }

    return $path;
}

/**
 * Pass JSON via temp files — Windows cmd line truncates/mangles large JSON (e.g. J&T in profiles).
 *
 * @return array<string, mixed>
 */
function order_remarks_run_python(string $mode, string $workbookPath, ?string $profileOverride = null, ?string $dstPath = null, ?array $layout = null, ?array $remarks = null): array
{
    $tempFiles = [];

    try {
        $profilesPath = order_remarks_write_temp_json(order_remarks_profiles_json());
        $tempFiles[] = $profilesPath;

        $args = [$workbookPath, $profilesPath, $mode];

        if ($mode === 'detect') {
            $args[] = $profileOverride ?? '';
        } elseif ($mode === 'write') {
            if ($dstPath === null || $layout === null || $remarks === null) {
                throw new RuntimeException('Write mode requires destination, layout, and remarks.');
            }
            $layoutJson = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $remarksJson = json_encode(array_values($remarks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($layoutJson === false || $remarksJson === false) {
                throw new RuntimeException('Could not encode export payload.');
            }
            $layoutPath = order_remarks_write_temp_json($layoutJson);
            $remarksPath = order_remarks_write_temp_json($remarksJson);
            $tempFiles[] = $layoutPath;
            $tempFiles[] = $remarksPath;
            $args[] = $dstPath;
            $args[] = $layoutPath;
            $args[] = $remarksPath;
        } else {
            throw new RuntimeException('Invalid Python mode: ' . $mode);
        }

        $built = order_remarks_python_command(...$args);
        $cmd = $built['cmd'] . ' 2>&1';

        $prevEnv = [];
        foreach ($built['env'] as $key => $val) {
            $prev = getenv($key);
            $prevEnv[$key] = $prev === false ? null : $prev;
            putenv($key . '=' . $val);
        }

        $out = shell_exec($cmd);

        foreach ($prevEnv as $key => $val) {
            if ($val === null) {
                putenv($key);
            } else {
                putenv($key . '=' . $val);
            }
        }

        if (!is_string($out) || trim($out) === '') {
            throw new RuntimeException('Python produced no output. Is openpyxl installed? (pip install openpyxl)');
        }

        $data = json_decode($out, true);
        if (!is_array($data)) {
            throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));
        }
        if (!empty($data['error'])) {
            throw new RuntimeException((string) $data['error']);
        }

        return $data;
    } finally {
        foreach ($tempFiles as $tmp) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }
}

function order_remarks_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['order_remarks_stash']) || !is_array($_SESSION['order_remarks_stash'])) {
        $_SESSION['order_remarks_stash'] = [];
        return;
    }
    foreach ($_SESSION['order_remarks_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['order_remarks_stash'][$token]);
        }
    }
}

function order_remarks_stash_upload(): string
{
    order_remarks_stash_prune();
    waybill_stamper_upload_preflight();

    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }

    $stashDir = order_remarks_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'xlsx_', $stashDir);
    if ($saved === []) {
        throw new RuntimeException('No file uploaded.');
    }

    $path = $saved[0];
    $token = bin2hex(random_bytes(16));
    $originalName = '';
    if (isset($field['name'])) {
        $originalName = is_array($field['name']) ? (string) ($field['name'][0] ?? '') : (string) $field['name'];
    }

    ims_session_start();
    if (!isset($_SESSION['order_remarks_stash']) || !is_array($_SESSION['order_remarks_stash'])) {
        $_SESSION['order_remarks_stash'] = [];
    }
    $_SESSION['order_remarks_stash'][$token] = [
        'path' => $path,
        'original_name' => $originalName,
        'expires' => time() + 3600,
    ];

    return $token;
}

function order_remarks_resolve_stash_path(string $token): string
{
    order_remarks_stash_prune();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        throw new RuntimeException('Invalid upload token.');
    }
    $stash = $_SESSION['order_remarks_stash'] ?? [];
    if (!is_array($stash)) {
        throw new RuntimeException('Invalid or expired upload token.');
    }
    $meta = $stash[$token] ?? null;
    if (!is_array($meta)) {
        throw new RuntimeException('Invalid or expired upload token.');
    }
    $path = (string) ($meta['path'] ?? '');
    if ($path === '' || !is_file($path)) {
        throw new RuntimeException('Stashed file missing. Upload again.');
    }

    return $path;
}

/**
 * @return array{layout:array<string,mixed>,orders:list<array<string,mixed>>,sampleRows:list<array<string,mixed>>,totalRows:int,profiles:list<array{id:string,label:string}>}
 */
function order_remarks_parse_workbook(string $token, ?string $profileOverride = null): array
{
    $path = order_remarks_resolve_stash_path($token);
    $override = $profileOverride !== null && $profileOverride !== '' ? $profileOverride : null;
    $data = order_remarks_run_python('detect', $path, $override);

    $layout = $data['layout'] ?? null;
    if (!is_array($layout)) {
        throw new RuntimeException('Format detection failed.');
    }

    $orders = $data['orders'] ?? [];
    if (!is_array($orders)) {
        $orders = [];
    }

    $sample = $data['sampleRows'] ?? [];
    if (!is_array($sample)) {
        $sample = [];
    }

    ims_session_start();
    if (!isset($_SESSION['order_remarks_layout']) || !is_array($_SESSION['order_remarks_layout'])) {
        $_SESSION['order_remarks_layout'] = [];
    }
    $_SESSION['order_remarks_layout'][$token] = $layout;

    return [
        'token' => $token,
        'layout' => $layout,
        'orders' => $orders,
        'sampleRows' => $sample,
        'totalRows' => (int) ($data['totalRows'] ?? count($orders)),
        'profiles' => order_remarks_profile_labels(),
    ];
}

/**
 * @param list<string> $remarks
 * @return array{download_token:string,download_filename:string}
 */
function order_remarks_export_workbook(string $token, array $remarks, ?array $layout = null): array
{
    $path = order_remarks_resolve_stash_path($token);

    ims_session_start();
    if ($layout === null) {
        $stored = $_SESSION['order_remarks_layout'][$token] ?? null;
        if (!is_array($stored)) {
            throw new RuntimeException('Layout not found. Parse the file again before export.');
        }
        $layout = $stored;
    }

    $outDir = order_remarks_storage_root() . '/outputs';
    $baseName = 'orders_remarks_' . date('Ymd_His') . '.xlsx';
    $outPath = $outDir . '/' . uniqid('out_', true) . '.xlsx';

    order_remarks_run_python('write', $path, null, $outPath, $layout, $remarks);

    if (!is_file($outPath)) {
        throw new RuntimeException('Export file was not created.');
    }

    $stash = $_SESSION['order_remarks_stash'][$token] ?? [];
    $original = is_array($stash) ? (string) ($stash['original_name'] ?? '') : '';
    $downloadName = $original !== '' ? pathinfo($original, PATHINFO_FILENAME) . '_remarks.xlsx' : $baseName;

    $downloadToken = order_remarks_register_download($outPath, $downloadName);

    return [
        'download_token' => $downloadToken,
        'download_filename' => $downloadName,
    ];
}

function order_remarks_register_download(string $absolutePath, string $downloadFilename): string
{
    ims_session_start();
    if (!isset($_SESSION['order_remarks_downloads']) || !is_array($_SESSION['order_remarks_downloads'])) {
        $_SESSION['order_remarks_downloads'] = [];
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['order_remarks_downloads'][$token] = [
        'path' => $absolutePath,
        'filename' => $downloadFilename,
        'expires' => time() + 3600,
    ];

    foreach ($_SESSION['order_remarks_downloads'] as $t => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['order_remarks_downloads'][$t]);
        }
    }

    return $token;
}

function order_remarks_serve_download(string $token): void
{
    order_remarks_require_access();
    ims_session_start();

    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        http_response_code(400);
        echo 'Invalid download token.';
        exit;
    }

    $meta = $_SESSION['order_remarks_downloads'][$token] ?? null;
    if (!is_array($meta)) {
        http_response_code(404);
        echo 'Download expired or not found.';
        exit;
    }

    if ((int) ($meta['expires'] ?? 0) < time()) {
        $path = (string) ($meta['path'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        unset($_SESSION['order_remarks_downloads'][$token]);
        http_response_code(410);
        echo 'Download link expired.';
        exit;
    }

    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'orders_remarks.xlsx');
    if ($path === '' || !is_file($path)) {
        unset($_SESSION['order_remarks_downloads'][$token]);
        http_response_code(404);
        echo 'File not found.';
        exit;
    }

    unset($_SESSION['order_remarks_downloads'][$token]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
    @unlink($path);
    exit;
}

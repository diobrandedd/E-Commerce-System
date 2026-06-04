<?php

declare(strict_types=1);

/**
 * Waybill stamper — run processor.py, secure temp storage, download tokens.
 */

require_once __DIR__ . '/rts_storage.php';

function waybill_stamper_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_WAYBILL_STAMPER,
        'waybill-stamper',
        ['uploads', 'outputs', 'stash'],
    );
}

function waybill_stamper_processor_script(): string
{
    return dirname(__DIR__) . '/waybill-stamper/processor.py';
}

/** @return array{id:int,role:string,dept_id:?int} */
function waybill_stamper_require_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        throw new RuntimeException('Not authenticated.');
    }
    if (!waybill_stamper_user_can_access($user)) {
        throw new RuntimeException('Forbidden.');
    }
    return $user;
}

/** @param array{role:string,dept_id:?int} $user */
function waybill_stamper_user_can_access(array $user): bool
{
    $role = (string) ($user['role'] ?? '');
    if ($role === 'executive' || $role === 'csr_tl' || $role === 'csr_staff') {
        return true;
    }
    if ($role === 'tl') {
        $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
        if ($deptId < 1) {
            return false;
        }
        try {
            $pdo = ims_pdo();
            $st = $pdo->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
            $st->execute([$deptId]);
            $name = (string) ($st->fetchColumn() ?: '');
            return strtolower($name) === 'csr';
        } catch (Throwable) {
            return false;
        }
    }
    return false;
}

function waybill_stamper_parse_ini_size(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return -1;
    }
    $unit = strtolower(substr($value, -1));
    $num = (float) $value;
    return (int) match ($unit) {
        'g' => $num * 1024 * 1024 * 1024,
        'm' => $num * 1024 * 1024,
        'k' => $num * 1024,
        default => $num,
    };
}

/**
 * Detect empty $_FILES after a multipart POST (often post_max_size / upload_max_filesize).
 */
function waybill_stamper_upload_preflight(): void
{
    if ($_FILES !== []) {
        return;
    }
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length < 1) {
        return;
    }

    $postMax = waybill_stamper_parse_ini_size((string) ini_get('post_max_size'));
    if ($postMax > 0 && $length > $postMax) {
        throw new RuntimeException(
            'Upload too large (' . round($length / 1048576, 1) . ' MB). '
            . 'Increase post_max_size and upload_max_filesize in php.ini (currently '
            . ini_get('post_max_size') . ' / ' . ini_get('upload_max_filesize')
            . ') and restart Apache.',
        );
    }

    throw new RuntimeException(
        'No files received. If you selected files, the upload may have failed (PHP limits: post_max_size='
        . ini_get('post_max_size') . ', upload_max_filesize=' . ini_get('upload_max_filesize') . ').',
    );
}

/** @return array<string, mixed>|null */
function waybill_stamper_files_field(string $base): ?array
{
    foreach ([$base, $base . '[]'] as $key) {
        if (!empty($_FILES[$key]) && is_array($_FILES[$key])) {
            return $_FILES[$key];
        }
    }
    foreach ($_FILES as $key => $field) {
        if (!is_array($field)) {
            continue;
        }
        $k = (string) $key;
        if ($k === $base || str_starts_with($k, $base)) {
            return $field;
        }
    }

    return null;
}

function waybill_stamper_utf8(string $text): string
{
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_convert_encoding')) {
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        if (is_string($clean) && $clean !== '') {
            return $clean;
        }
    }

    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
}

function waybill_stamper_resolve_python(): string
{
    if (!function_exists('shell_exec')) {
        throw new RuntimeException(
            'PHP shell_exec is disabled. Enable it in php.ini (remove shell_exec from disable_functions) and restart Apache.',
        );
    }

    $candidates = ['python', 'python3'];
    if (DIRECTORY_SEPARATOR === '\\') {
        $candidates = ['py', 'python', 'python3', ...$candidates];
    }

    foreach (array_unique($candidates) as $bin) {
        $cmd = $bin . ' --version 2>&1';
        $out = shell_exec($cmd);
        if (is_string($out) && stripos($out, 'python') !== false) {
            return $bin;
        }
    }

    throw new RuntimeException(
        'Python is not available on this server. Install Python 3 and add it to PATH (see waybill-stamper/SETUP.md).',
    );
}

/**
 * @param array<string, mixed> $fileField $_FILES['pdfs'] or $_FILES['excels']
 * @param list<string> $allowedExt
 * @return list<string> saved absolute paths
 */
function waybill_stamper_collect_uploads(array $fileField, array $allowedExt, string $prefix, ?string $uploadDir = null): array
{
    $saved = [];
    $names = $fileField['name'] ?? null;
    if (!is_array($names)) {
        $names = $names !== null && $names !== '' ? [$names] : [];
    }
    if ($names === []) {
        return [];
    }

    $tmps = (array) ($fileField['tmp_name'] ?? []);
    $errs = (array) ($fileField['error'] ?? []);

    $uploadDir = $uploadDir ?? waybill_stamper_storage_root() . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    foreach ($names as $i => $name) {
        $name = (string) $name;
        if ($name === '') {
            continue;
        }
        $err = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $hint = match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => ' (file too large — increase upload_max_filesize / post_max_size in php.ini)',
                UPLOAD_ERR_PARTIAL => ' (upload interrupted)',
                UPLOAD_ERR_NO_FILE => ' (no file sent)',
                default => ' (error ' . $err . ')',
            };
            throw new RuntimeException('Upload failed for ' . $name . $hint . '.');
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException($name . ' has invalid type. Allowed: ' . implode(', ', $allowedExt));
        }
        $tmp = (string) ($tmps[$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Invalid upload for ' . $name);
        }
        $dest = $uploadDir . '/' . uniqid($prefix, true) . '.' . $ext;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Could not save upload: ' . $name);
        }
        $saved[] = $dest;
    }

    return $saved;
}

function waybill_stamper_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['waybill_stash']) || !is_array($_SESSION['waybill_stash'])) {
        $_SESSION['waybill_stash'] = [];
        return;
    }
    foreach ($_SESSION['waybill_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['waybill_stash'][$token]);
        }
    }
}

/** Stash one uploaded file; returns session token (one file per HTTP request). */
function waybill_stamper_stash_upload(string $kind): string
{
    waybill_stamper_stash_prune();

    $allowed = $kind === 'pdf' ? ['pdf'] : ['xlsx', 'xls'];
    $prefix = $kind === 'pdf' ? 'pdf_' : 'xls_';

    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }

    $stashDir = waybill_stamper_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, $allowed, $prefix, $stashDir);
    if ($saved === []) {
        throw new RuntimeException('No file uploaded.');
    }

    $path = $saved[0];
    $token = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['waybill_stash']) || !is_array($_SESSION['waybill_stash'])) {
        $_SESSION['waybill_stash'] = [];
    }
    $_SESSION['waybill_stash'][$token] = [
        'path' => $path,
        'kind' => $kind,
        'expires' => time() + 3600,
    ];

    return $token;
}

/**
 * @param list<string> $tokens
 * @return list<string> absolute paths
 */
function waybill_stamper_resolve_stash_tokens(array $tokens, string $kind): array
{
    waybill_stamper_stash_prune();
    ims_session_start();
    $stash = $_SESSION['waybill_stash'] ?? [];
    if (!is_array($stash)) {
        return [];
    }

    $paths = [];
    foreach ($tokens as $raw) {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) $raw));
        if ($token === '') {
            continue;
        }
        $meta = $stash[$token] ?? null;
        if (!is_array($meta) || (string) ($meta['kind'] ?? '') !== $kind) {
            throw new RuntimeException('Invalid or expired upload token.');
        }
        $path = (string) ($meta['path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException('Stashed file missing. Upload again.');
        }
        $paths[] = $path;
    }

    return $paths;
}

/** @param list<string> $tokens */
function waybill_stamper_release_stash_tokens(array $tokens): void
{
    ims_session_start();
    if (!isset($_SESSION['waybill_stash']) || !is_array($_SESSION['waybill_stash'])) {
        return;
    }
    foreach ($tokens as $raw) {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) $raw));
        if ($token === '') {
            continue;
        }
        $meta = $_SESSION['waybill_stash'][$token] ?? null;
        if (is_array($meta)) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
        }
        unset($_SESSION['waybill_stash'][$token]);
    }
}

/**
 * @return array{
 *   total:int,
 *   matched:int,
 *   unmatched:int,
 *   unmatched_list:list<string>,
 *   download_token:string,
 *   download_filename:string,
 *   log_excerpt:string
 * }
 */
function waybill_stamper_process_uploads(array $pdfFiles, array $excelFiles): array
{
    if ($pdfFiles === []) {
        throw new RuntimeException('No PDF waybills uploaded.');
    }
    if ($excelFiles === []) {
        throw new RuntimeException('No Excel order sheets uploaded.');
    }

    $script = waybill_stamper_processor_script();
    if (!is_file($script)) {
        throw new RuntimeException('Waybill processor script is missing.');
    }

    $root = waybill_stamper_storage_root();
    $uploadDir = $root . '/uploads';
    $outputDir = $root . '/outputs';

    $pdfList = $uploadDir . '/' . uniqid('pdf_list_', true) . '.json';
    $excelList = $uploadDir . '/' . uniqid('xls_list_', true) . '.json';
    $outputFile = $outputDir . '/stamped_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $logFile = $uploadDir . '/' . uniqid('log_', true) . '.txt';

    file_put_contents($pdfList, json_encode($pdfFiles, JSON_UNESCAPED_SLASHES));
    file_put_contents($excelList, json_encode($excelFiles, JSON_UNESCAPED_SLASHES));

    $python = waybill_stamper_resolve_python();
    $pyArgs = $python === 'py' ? ' -3' : '';
    $env = '';
    if (DIRECTORY_SEPARATOR === '\\') {
        $pathAdd = [
            'C:\\Program Files\\Tesseract-OCR',
            'C:\\poppler\\Library\\bin',
        ];
        $pathVal = implode(';', $pathAdd) . ';%PATH%';
        $env = 'set "PYTHONIOENCODING=utf-8"&& set "PATH=' . $pathVal . '"&& ';
    }
    $cmd = $env . $python . $pyArgs . ' ' . escapeshellarg($script)
        . ' --pdfs ' . escapeshellarg($pdfList)
        . ' --excels ' . escapeshellarg($excelList)
        . ' --output ' . escapeshellarg($outputFile)
        . ' --log ' . escapeshellarg($logFile)
        . ' 2>&1';

    $output = shell_exec($cmd);
    if (!is_string($output)) {
        $output = '';
    }

    foreach (array_merge($pdfFiles, $excelFiles) as $f) {
        if (str_contains($f, '/stash/') || str_contains($f, '\\stash\\')) {
            continue;
        }
        @unlink($f);
    }
    @unlink($pdfList);
    @unlink($excelList);

    $log = is_file($logFile) ? (string) file_get_contents($logFile) : $output;
    @unlink($logFile);

    if (!is_file($outputFile)) {
        $hint = trim($output) !== '' ? $output : $log;
        $hint = waybill_stamper_utf8($hint);
        if (strlen($hint) > 2000) {
            $hint = substr($hint, -2000);
        }
        throw new RuntimeException(
            'Processing failed. ' . ($hint !== '' ? 'Log: ' . $hint : 'Check Python, Tesseract, and Poppler.'),
        );
    }

    preg_match('/STAT_TOTAL:(\d+)/', $output, $mT);
    preg_match('/STAT_MATCHED:(\d+)/', $output, $mM);
    preg_match('/STAT_UNMATCHED:(\d+)/', $output, $mU);

    $unmatched = [];
    if (preg_match_all('/UNMATCHED_TN:(.+)/', $output, $mUN) && !empty($mUN[1])) {
        $unmatched = array_map('trim', $mUN[1]);
    }

    $downloadFilename = 'Stamped_Waybills_' . date('Y-m-d') . '.pdf';
    $token = waybill_stamper_register_download($outputFile, $downloadFilename);

    $logExcerpt = waybill_stamper_utf8($log);
    if (strlen($logExcerpt) > 4000) {
        $logExcerpt = '…' . substr($logExcerpt, -4000);
    }

    return [
        'total' => (int) ($mT[1] ?? 0),
        'matched' => (int) ($mM[1] ?? 0),
        'unmatched' => (int) ($mU[1] ?? 0),
        'unmatched_list' => $unmatched,
        'download_token' => $token,
        'download_filename' => $downloadFilename,
        'log_excerpt' => $logExcerpt,
    ];
}

function waybill_stamper_register_download(string $absolutePath, string $downloadFilename): string
{
    ims_session_start();
    if (!isset($_SESSION['waybill_downloads']) || !is_array($_SESSION['waybill_downloads'])) {
        $_SESSION['waybill_downloads'] = [];
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['waybill_downloads'][$token] = [
        'path' => $absolutePath,
        'filename' => $downloadFilename,
        'expires' => time() + 3600,
    ];

    // Prune expired entries and delete orphaned files
    foreach ($_SESSION['waybill_downloads'] as $t => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['waybill_downloads'][$t]);
        }
    }

    return $token;
}

function waybill_stamper_serve_download(string $token): void
{
    waybill_stamper_require_access();
    ims_session_start();

    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        http_response_code(400);
        echo 'Invalid download token.';
        exit;
    }

    $meta = $_SESSION['waybill_downloads'][$token] ?? null;
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
        unset($_SESSION['waybill_downloads'][$token]);
        http_response_code(410);
        echo 'Download link expired.';
        exit;
    }

    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'Stamped_Waybills.pdf');
    if ($path === '' || !is_file($path)) {
        unset($_SESSION['waybill_downloads'][$token]);
        http_response_code(404);
        echo 'File not found.';
        exit;
    }

    unset($_SESSION['waybill_downloads'][$token]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
    @unlink($path);
    exit;
}

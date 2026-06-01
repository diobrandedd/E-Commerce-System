<?php

declare(strict_types=1);

/**
 * PDF → Excel extractor (Shopee waybills → iPYX order sheet).
 */

require_once __DIR__ . '/waybill_stamper.php';

function pdf_to_excel_storage_root(): string
{
    $root = dirname(__DIR__) . '/storage/pdf-to-excel';
    foreach (['stash', 'outputs', 'work'] as $sub) {
        $dir = $root . '/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create pdf-to-excel storage directory.');
        }
    }

    return $root;
}

function pdf_to_excel_extractor_script(): string
{
    return dirname(__DIR__) . '/pdf-to-excel/extractor.py';
}

/** @return array{id:int,role:string,dept_id:?int} */
function pdf_to_excel_require_access(): array
{
    return waybill_stamper_require_access();
}

function pdf_to_excel_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_stash']) || !is_array($_SESSION['pdf_to_excel_stash'])) {
        $_SESSION['pdf_to_excel_stash'] = [];

        return;
    }
    foreach ($_SESSION['pdf_to_excel_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['pdf_to_excel_stash'][$token]);
        }
    }
}

function pdf_to_excel_stash_upload(): string
{
    pdf_to_excel_stash_prune();

    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }

    $stashDir = pdf_to_excel_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, ['pdf'], 'pdf_', $stashDir);
    if ($saved === []) {
        throw new RuntimeException('No file uploaded.');
    }

    $path = $saved[0];
    $token = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_stash']) || !is_array($_SESSION['pdf_to_excel_stash'])) {
        $_SESSION['pdf_to_excel_stash'] = [];
    }
    $_SESSION['pdf_to_excel_stash'][$token] = [
        'path' => $path,
        'expires' => time() + 3600,
    ];

    return $token;
}

/**
 * @param list<string> $tokens
 * @return list<string>
 */
function pdf_to_excel_resolve_stash_tokens(array $tokens): array
{
    pdf_to_excel_stash_prune();
    ims_session_start();
    $stash = $_SESSION['pdf_to_excel_stash'] ?? [];
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
        if (!is_array($meta)) {
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
function pdf_to_excel_release_stash_tokens(array $tokens): void
{
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_stash']) || !is_array($_SESSION['pdf_to_excel_stash'])) {
        return;
    }
    foreach ($tokens as $raw) {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) $raw));
        if ($token === '') {
            continue;
        }
        $meta = $_SESSION['pdf_to_excel_stash'][$token] ?? null;
        if (is_array($meta)) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
        }
        unset($_SESSION['pdf_to_excel_stash'][$token]);
    }
}

function pdf_to_excel_jobs_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_jobs']) || !is_array($_SESSION['pdf_to_excel_jobs'])) {
        $_SESSION['pdf_to_excel_jobs'] = [];

        return;
    }
    foreach ($_SESSION['pdf_to_excel_jobs'] as $token => $job) {
        if (!is_array($job) || (int) ($job['expires'] ?? 0) < time()) {
            pdf_to_excel_delete_job_files($job);
            unset($_SESSION['pdf_to_excel_jobs'][$token]);
        }
    }
}

/** @param array<string, mixed>|null $job */
function pdf_to_excel_delete_job_files(?array $job): void
{
    if (!is_array($job)) {
        return;
    }
    $files = $job['files'] ?? [];
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $excelPath = (string) ($f['excel_path'] ?? '');
        if ($excelPath !== '' && is_file($excelPath)) {
            @unlink($excelPath);
        }
    }
}

/**
 * @param list<string> $pdfPaths
 * @return array{job_token:string,files:list<array<string,mixed>>,log_excerpt:string}
 */
function pdf_to_excel_extract(array $pdfPaths): array
{
    if ($pdfPaths === []) {
        throw new RuntimeException('No PDF files to extract.');
    }

    $script = pdf_to_excel_extractor_script();
    if (!is_file($script)) {
        throw new RuntimeException('PDF extractor script is missing.');
    }

    $root = pdf_to_excel_storage_root();
    $workDir = $root . '/work/' . bin2hex(random_bytes(8));
    $outputDir = $workDir . '/out';
    if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
        throw new RuntimeException('Could not create work directory.');
    }

    $pdfList = $workDir . '/pdfs.json';
    $reportPath = $workDir . '/report.json';
    file_put_contents($pdfList, json_encode($pdfPaths, JSON_UNESCAPED_SLASHES));

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
        . ' --outdir ' . escapeshellarg($outputDir)
        . ' --report ' . escapeshellarg($reportPath)
        . ' 2>&1';

    $output = shell_exec($cmd);
    if (!is_string($output)) {
        $output = '';
    }

    @unlink($pdfList);

    if (!is_file($reportPath)) {
        $hint = waybill_stamper_utf8(trim($output));
        if (strlen($hint) > 2000) {
            $hint = substr($hint, -2000);
        }
        pdf_to_excel_rmdir($workDir);
        throw new RuntimeException(
            'Extraction failed. ' . ($hint !== '' ? $hint : 'Check Python and pdfplumber.'),
        );
    }

    $reportRaw = file_get_contents($reportPath);
    @unlink($reportPath);
    $report = json_decode((string) $reportRaw, true);
    if (!is_array($report)) {
        pdf_to_excel_rmdir($workDir);
        throw new RuntimeException('Invalid extractor report.');
    }

    $jobFiles = [];
    foreach ($report as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $excelName = (string) ($entry['excel'] ?? '');
        if ($excelName === '') {
            continue;
        }
        $excelPath = $outputDir . '/' . $excelName;
        if (!is_file($excelPath)) {
            continue;
        }

        $rows = [];
        foreach ($entry['rows'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = [
                'page' => (int) ($row['page'] ?? 0),
                'tracking' => (string) ($row['tracking'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'items' => (string) ($row['items'] ?? ''),
                'courier' => (string) ($row['courier'] ?? ''),
                'complete' => (bool) ($row['complete'] ?? false),
                'missing_fields' => is_array($row['missing_fields'] ?? null)
                    ? array_values($row['missing_fields'])
                    : [],
            ];
        }

        $jobFiles[] = [
            'pdf_name' => (string) ($entry['pdf'] ?? basename($excelName, '_Orders.xlsx') . '.pdf'),
            'excel_filename' => $excelName,
            'excel_path' => $excelPath,
            'rows' => $rows,
            'total' => (int) ($entry['total'] ?? count($rows)),
            'complete' => (int) ($entry['complete'] ?? 0),
            'missing' => (int) ($entry['missing'] ?? 0),
        ];
    }

    if ($jobFiles === []) {
        pdf_to_excel_rmdir($workDir);
        throw new RuntimeException('No Excel files were generated.');
    }

    $jobToken = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_jobs']) || !is_array($_SESSION['pdf_to_excel_jobs'])) {
        $_SESSION['pdf_to_excel_jobs'] = [];
    }
    pdf_to_excel_jobs_prune();

    $_SESSION['pdf_to_excel_jobs'][$jobToken] = [
        'expires' => time() + 3600,
        'confirmed' => false,
        'work_dir' => $workDir,
        'files' => $jobFiles,
    ];

    $logExcerpt = waybill_stamper_utf8($output);
    if (strlen($logExcerpt) > 4000) {
        $logExcerpt = '…' . substr($logExcerpt, -4000);
    }

    $apiFiles = [];
    foreach ($jobFiles as $f) {
        $apiFiles[] = [
            'pdf_name' => $f['pdf_name'],
            'excel_filename' => $f['excel_filename'],
            'rows' => $f['rows'],
            'total' => $f['total'],
            'complete' => $f['complete'],
            'missing' => $f['missing'],
        ];
    }

    return [
        'job_token' => $jobToken,
        'files' => $apiFiles,
        'log_excerpt' => $logExcerpt,
    ];
}

/**
 * @return array{downloads:list<array{filename:string,download_token:string}>}
 */
function pdf_to_excel_confirm(string $jobToken): array
{
    pdf_to_excel_jobs_prune();
    ims_session_start();

    $jobToken = preg_replace('/[^a-f0-9]/', '', strtolower($jobToken));
    if ($jobToken === '') {
        throw new RuntimeException('Invalid job token.');
    }

    $job = $_SESSION['pdf_to_excel_jobs'][$jobToken] ?? null;
    if (!is_array($job)) {
        throw new RuntimeException('Job expired or not found. Extract again.');
    }
    if (!empty($job['confirmed'])) {
        throw new RuntimeException('Job already confirmed.');
    }

    $downloads = [];
    $files = $job['files'] ?? [];
    if (!is_array($files)) {
        throw new RuntimeException('Job has no files.');
    }

    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $path = (string) ($f['excel_path'] ?? '');
        $filename = (string) ($f['excel_filename'] ?? 'Orders.xlsx');
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException('Excel file missing for ' . $filename);
        }
        $downloads[] = [
            'filename' => $filename,
            'download_token' => pdf_to_excel_register_download($path, $filename, $jobToken),
        ];
    }

    $_SESSION['pdf_to_excel_jobs'][$jobToken]['confirmed'] = true;

    return ['downloads' => $downloads];
}

function pdf_to_excel_register_download(string $absolutePath, string $filename, string $jobToken): string
{
    ims_session_start();
    if (!isset($_SESSION['pdf_to_excel_downloads']) || !is_array($_SESSION['pdf_to_excel_downloads'])) {
        $_SESSION['pdf_to_excel_downloads'] = [];
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['pdf_to_excel_downloads'][$token] = [
        'path' => $absolutePath,
        'filename' => $filename,
        'job_token' => $jobToken,
        'expires' => time() + 3600,
    ];

    foreach ($_SESSION['pdf_to_excel_downloads'] as $t => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['pdf_to_excel_downloads'][$t]);
        }
    }

    return $token;
}

function pdf_to_excel_serve_download(string $token): void
{
    pdf_to_excel_require_access();
    ims_session_start();

    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        http_response_code(400);
        echo 'Invalid download token.';
        exit;
    }

    $meta = $_SESSION['pdf_to_excel_downloads'][$token] ?? null;
    if (!is_array($meta)) {
        http_response_code(404);
        echo 'Download expired or not found.';
        exit;
    }

    $jobToken = preg_replace('/[^a-f0-9]/', '', strtolower((string) ($meta['job_token'] ?? '')));
    $job = $_SESSION['pdf_to_excel_jobs'][$jobToken] ?? null;
    if (!is_array($job) || empty($job['confirmed'])) {
        http_response_code(403);
        echo 'Download not authorized. Confirm the review first.';
        exit;
    }

    if ((int) ($meta['expires'] ?? 0) < time()) {
        $path = (string) ($meta['path'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        unset($_SESSION['pdf_to_excel_downloads'][$token]);
        http_response_code(410);
        echo 'Download link expired.';
        exit;
    }

    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'Orders.xlsx');
    if ($path === '' || !is_file($path)) {
        unset($_SESSION['pdf_to_excel_downloads'][$token]);
        http_response_code(404);
        echo 'File not found.';
        exit;
    }

    unset($_SESSION['pdf_to_excel_downloads'][$token]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: no-store');
    readfile($path);

    pdf_to_excel_maybe_cleanup_job($jobToken, $path);
    exit;
}

function pdf_to_excel_maybe_cleanup_job(string $jobToken, string $downloadedPath): void
{
    ims_session_start();
    $job = $_SESSION['pdf_to_excel_jobs'][$jobToken] ?? null;
    if (!is_array($job)) {
        @unlink($downloadedPath);

        return;
    }

    $files = $job['files'] ?? [];
    if (!is_array($files)) {
        return;
    }

    $allGone = true;
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $p = (string) ($f['excel_path'] ?? '');
        if ($p !== '' && is_file($p)) {
            if ($p === $downloadedPath) {
                @unlink($p);
            } else {
                $allGone = false;
            }
        }
    }

    if ($allGone) {
        $workDir = (string) ($job['work_dir'] ?? '');
        pdf_to_excel_delete_job_files($job);
        unset($_SESSION['pdf_to_excel_jobs'][$jobToken]);
        if ($workDir !== '' && is_dir($workDir)) {
            pdf_to_excel_rmdir($workDir);
        }
    }
}

function pdf_to_excel_rmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            pdf_to_excel_rmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

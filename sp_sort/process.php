<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = sp_sort_user();
$pdo = rts_pdo();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 302);
    exit;
}

$error = '';
$uploadedPaths = [];
$sourceNames = [];
$allowedExts = ['xlsx', 'xls'];

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        if ($name === '' || $name === null) {
            continue;
        }
        $err = (int) ($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $error = 'Upload error on file ' . ($i + 1);
            break;
        }
        $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $error = 'Only .xlsx and .xls files are allowed.';
            break;
        }
        $dest = sp_sort_upload_dir() . '/' . uniqid('up_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
            $error = 'Failed to save uploaded file.';
            break;
        }
        $uploadedPaths[] = $dest;
        $sourceNames[] = (string) $name;
    }
}

if ($error === '' && $uploadedPaths === []) {
    $error = 'Please upload at least one Excel file.';
}
if ($error === '' && count($uploadedPaths) > 3) {
    $error = 'Maximum 3 files allowed.';
}

if ($error === '') {
    $hasHeader = isset($_POST['has_header']);
    $sortDir = ($_POST['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    $merged = sp_sort_merge_uploads($uploadedPaths, $hasHeader);
    if ($merged['error'] !== null) {
        $error = $merged['error'];
    } else {
        try {
            $batchId = sp_sort_create_batch(
                $pdo,
                $user['id'],
                $merged,
                $sourceNames,
                $sortDir
            );
            foreach ($uploadedPaths as $p) {
                if (is_file($p)) {
                    @unlink($p);
                }
            }
            header('Location: workflow.php?batch=' . $batchId, true, 302);
            exit;
        } catch (Throwable $e) {
            $error = 'Could not save batch: ' . $e->getMessage();
        }
    }
}

foreach ($uploadedPaths as $p) {
    if (is_file($p)) {
        @unlink($p);
    }
}

header('Location: index.php?error=' . urlencode($error), true, 302);
exit;

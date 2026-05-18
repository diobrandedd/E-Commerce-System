<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/labels.php';
require_once dirname(__DIR__) . '/includes/sp_sort_service.php';
require_once dirname(__DIR__) . '/includes/deliveries_review_nav.php';
require_once dirname(__DIR__) . '/includes/admin_sidebar.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

rts_require_roles([RTS_ROLE_ADMIN], '../login.php');

function sp_sort_delivery_nav_badge(): string
{
    try {
        return rts_delivery_review_nav_badge_html(rts_pdo());
    } catch (Throwable $e) {
        return '';
    }
}

function sp_sort_user(): array
{
    $u = rts_auth_user();
    if ($u === null) {
        header('Location: ../login.php', true, 302);
        exit;
    }
    return $u;
}

function sp_sort_batch_or_404(PDO $pdo, int $batchId): array
{
    $st = $pdo->prepare(
        'SELECT b.*, u.username AS submitted_by
         FROM sp_sort_batches b
         INNER JOIN app_users u ON u.id = b.submitted_by_user_id
         WHERE b.id = ? LIMIT 1'
    );
    $st->execute([$batchId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo 'Batch not found.';
        exit;
    }
    return $row;
}

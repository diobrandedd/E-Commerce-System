<?php

declare(strict_types=1);

/**
 * Count of delivery line items that still need attention (admin review).
 * Includes missing/blank tracking or statuses that need manual handling.
 */
function rts_delivery_review_attention_count(PDO $pdo): int
{
    $sql = 'SELECT COUNT(*) FROM delivery_batch_items i
      WHERE i.voided_at IS NULL
      AND (
        i.tracking_number IS NULL OR TRIM(i.tracking_number) = \'\'
        OR i.item_status IN (\'needs_manual\',\'ambiguous\')
      )';

    return (int) $pdo->query($sql)->fetchColumn();
}

/** Inline badge HTML for nav link (red pill with count). */
function rts_delivery_review_nav_badge_html(PDO $pdo): string
{
    try {
        $n = rts_delivery_review_attention_count($pdo);
    } catch (Throwable) {
        return '';
    }
    if ($n < 1) {
        return '';
    }
    $label = $n > 99 ? '99+' : (string) $n;
    $t = 'Delivery items missing tracking or needing manual review';
    $s = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return ' <span class="nav-attn-badge" title="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '" style="'
        . 'display:inline-flex;min-width:1.15rem;height:1.15rem;padding:0 0.35rem;align-items:center;'
        . 'justify-content:center;background:#c0392b;color:#fff;border-radius:999px;font-size:0.62rem;'
        . 'font-weight:800;margin-left:0.15rem;vertical-align:text-top;line-height:1;">' . $s . '</span>';
}

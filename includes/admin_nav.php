<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Admin sidebar / mobile menu links.
 *
 * @param string $active One of: dashboard, record, records, deliveries, sp_sort, sp_sort_records
 * @param string $prefix Path prefix (e.g. '' or '../')
 * @param string $deliveryNavBadge HTML snippet for deliveries review badge
 * @param string|null $deliveriesHref Override deliveries link (e.g. 'review.php' from deliveries/)
 */
function rts_admin_nav_links(string $active, string $prefix = '', string $deliveryNavBadge = '', ?string $deliveriesHref = null): void
{
    if ($deliveriesHref === null) {
        $deliveriesHref = $prefix . 'deliveries/review.php';
    }

    $items = [];
    $u = rts_auth_user();
    if ($u !== null && rts_role_is_executive((string) ($u['role'] ?? ''))) {
        $items[] = ['id' => 'executive', 'href' => $prefix . 'executive.php', 'label' => 'Executive settings', 'raw' => false];
    }
    $items = array_merge($items, [
        ['id' => 'dashboard', 'href' => $prefix . 'dashboard.php', 'label' => 'Dashboard', 'raw' => false],
        ['id' => 'record', 'href' => $prefix . 'record.php', 'label' => 'Record RTS', 'raw' => false],
        ['id' => 'records', 'href' => $prefix . 'records.php', 'label' => 'Records', 'raw' => false],
        ['id' => 'deliveries', 'href' => $prefix . 'deliveries/index.php', 'label' => 'Deliveries upload', 'raw' => false],
        ['id' => 'deliveries_review', 'href' => $deliveriesHref, 'label' => 'Deliveries review' . $deliveryNavBadge, 'raw' => true],
        ['id' => 'sp_sort', 'href' => $prefix . 'sp_sort/index.php', 'label' => 'SP Sort', 'raw' => false],
        ['id' => 'sp_sort_records', 'href' => $prefix . 'sp_sort/batches.php', 'label' => 'SP Sort records', 'raw' => false],
        ['id' => 'settings', 'href' => $prefix . 'settings.php', 'label' => 'Settings', 'raw' => false],
        ['id' => 'logout', 'href' => $prefix . 'logout.php', 'label' => 'Sign out', 'raw' => false],
    ]);

    foreach ($items as $item) {
        $cls = $item['id'] === $active ? ' class="active"' : '';
        $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
        $text = $item['raw']
            ? $item['label']
            : htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $href . '"' . $cls . '>' . $text . "</a>\n";
    }
}

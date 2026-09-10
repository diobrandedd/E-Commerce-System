<?php

declare(strict_types=1);

if (!function_exists('ims_render_exec_sidebar')) {
    function ims_render_exec_sidebar(string $activeId = 'dashboard'): void
    {
        $pendingCount = 0;
        try {
            $pdo = ims_pdo();
            $st = $pdo->query("SELECT COUNT(*) FROM edit_requests WHERE status = 'pending'");
            $pendingCount = $st ? (int) $st->fetchColumn() : 0;
        } catch (Throwable $e) {
            $pendingCount = 0;
        }

        $items = [
            ['id' => 'dashboard', 'href' => 'dashboard.php', 'label' => 'Dashboard'],
            ['id' => 'staff_management', 'href' => 'staff_management.php', 'label' => 'Staff management'],
            ['id' => 'edit_requests', 'href' => 'edit_requests.php', 'label' => 'Edit requests', 'badge' => $pendingCount],
            ['id' => 'stock_entries', 'href' => '../dept/stock_entries.php', 'label' => 'Stock entries'],
            ['id' => 'products', 'href' => '../dept/products.php', 'label' => 'Costing module'],
            ['id' => 'settings', 'href' => '../auth/settings.php', 'label' => 'Settings'],
            ['id' => 'logout', 'href' => '../auth/logout.php', 'label' => 'Logout'],
        ];
        ?>
        <aside class="exec-sidebar">
          <h2 class="exec-sidebar-title">Executive navigation</h2>
          <nav class="exec-sidebar-nav" aria-label="Executive quick navigation">
            <?php foreach ($items as $item): ?>
              <?php
                $isActive = ($activeId === (string) $item['id']);
                $label = (string) $item['label'];
                $href = (string) $item['href'];
                $badge = (int) ($item['badge'] ?? 0);
              ?>
              <a class="exec-sidebar-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($badge > 0): ?>
                  <span class="exec-sidebar-badge"><?= $badge ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </nav>
        </aside>
        <?php
    }
}


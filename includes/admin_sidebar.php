<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_nav.php';

/**
 * @param string $active dashboard|record|records|deliveries|sp_sort|sp_sort_records
 * @param string $prefix '' or '../'
 */
function rts_admin_sidebar(string $active, string $prefix = '', string $deliveryNavBadge = '', ?string $deliveriesHref = null): void
{
    if ($deliveriesHref === null) {
        $deliveriesHref = $prefix . 'deliveries/review.php';
    }
    $logo = $prefix . 'assets/sys_img/pyxfoodpr.png';
    ?>
<div class="mobile-topbar">
  <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">☰</button>
  <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Pyxfood" style="height:28px;width:auto;border-radius:6px;" onerror="this.style.display='none'">
  <div style="font-weight:700;">RTS system</div>
</div>
<div id="mobileMenuPanel" class="mobile-menu-panel" aria-hidden="true">
  <nav class="nav-links">
    <?php rts_admin_nav_links($active, $prefix, $deliveryNavBadge, $deliveriesHref); ?>
  </nav>
</div>
<aside class="sidebar">
  <div>
    <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Pyxfood" style="height:42px;width:auto;max-width:100%;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
    <h1>RTS system</h1>
    <p style="margin:0.25rem 0 0;font-size:0.78rem;color:#7e6a4b;">Returns &amp; SP Sort</p>
  </div>
  <nav class="nav-links">
    <?php rts_admin_nav_links($active, $prefix, $deliveryNavBadge, $deliveriesHref); ?>
  </nav>
</aside>
<?php
}

function rts_admin_sidebar_script(): void
{
    ?>
<script>
(function () {
  var btn = document.getElementById('mobileMenuBtn');
  var panel = document.getElementById('mobileMenuPanel');
  if (!btn || !panel) return;
  function setOpen(open) {
    panel.classList.toggle('open', !!open);
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
  }
  btn.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });
  panel.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { setOpen(false); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });
})();
</script>
<?php
}

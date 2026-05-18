<?php

declare(strict_types=1);

/** Shared sidebar + mobile menu styles for admin RTS pages. */
?>
<style>
  body.admin-layout { margin:0; font-family:system-ui,sans-serif; display:flex; min-height:100vh; }
  .admin-layout .sidebar {
    width:220px; padding:1.25rem 1rem; border-right:1px solid #e8dcc5; background:#fff5df;
    display:flex; flex-direction:column; gap:1.25rem; position:sticky; top:0; align-self:flex-start;
    height:100vh; overflow-y:auto; flex-shrink:0;
  }
  .admin-layout .sidebar h1 { margin:0; font-size:1.05rem; font-weight:650; }
  .admin-layout .nav-links a {
    display:block; padding:0.4rem 0.1rem; color:#7e6a4b; text-decoration:none; font-size:0.9rem;
  }
  .admin-layout .nav-links a.active { color:#d99200; font-weight:600; }
  .admin-layout .nav-links a:hover { color:#4a381d; }
  .admin-layout main { flex:1; min-width:0; }
  .admin-layout .mobile-topbar {
    display:none; position:fixed; top:0; left:0; right:0; height:56px; z-index:90;
    background:#fff5df; border-bottom:1px solid #e8dcc5; padding:0 0.85rem; align-items:center; gap:0.75rem;
  }
  .admin-layout .mobile-menu-btn {
    background:#fff7ea; border:1px solid #e8dcc5; border-radius:10px; padding:0.45rem 0.6rem; cursor:pointer;
  }
  .admin-layout .mobile-menu-panel {
    display:block; position:fixed; z-index:95; top:56px; left:0; width:240px; bottom:0;
    background:#fff5df; border-right:1px solid #e8dcc5; padding:1rem 0.9rem; overflow-y:auto;
    transform:translateX(-105%); opacity:0; pointer-events:none;
    transition:transform .22s ease, opacity .22s ease;
  }
  .admin-layout .mobile-menu-panel.open { transform:translateX(0); opacity:1; pointer-events:auto; }
  @media (max-width:720px) {
    .admin-layout .sidebar { display:none !important; }
    .admin-layout .mobile-topbar { display:flex; }
    .admin-layout main { padding-top:72px; }
  }
</style>

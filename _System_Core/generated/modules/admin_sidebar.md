---
title: admin_sidebar
source: includes/admin_sidebar.php
generated: 2026-08-15T10:43:43+02:00
type: module
function_count: 3
---


# admin_sidebar

> Source: `includes/admin_sidebar.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/admin_nav.php`

## rts_admin_sidebar {#rts_admin_sidebar}

**Callers (3):**

- `sp_sort/batches.php`:76 — `<?php rts_admin_sidebar('sp_sort_records', '../', sp_sort_delivery_nav_badge()); ?>`
- `sp_sort/index.php`:21 — `<?php rts_admin_sidebar('sp_sort', '../', sp_sort_delivery_nav_badge()); ?>`
- `sp_sort/workflow.php`:57 — `<?php rts_admin_sidebar('sp_sort', '../', sp_sort_delivery_nav_badge()); ?>`

## rts_admin_sidebar_script {#rts_admin_sidebar_script}

**Callers (3):**

- `sp_sort/batches.php`:185 — `<?php rts_admin_sidebar_script(); ?>`
- `sp_sort/index.php`:78 — `<?php rts_admin_sidebar_script(); ?>`
- `sp_sort/workflow.php`:167 — `<?php rts_admin_sidebar_script(); ?>`

## setOpen {#setopen}

**Callers (24):**

- `includes/admin_sidebar.php`:53 — `btn.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });`
- `includes/admin_sidebar.php`:55 — `a.addEventListener('click', function () { setOpen(false); });`
- `includes/admin_sidebar.php`:58 — `if (e.key === 'Escape') setOpen(false);`
- `dashboard.php`:604 — `setOpen(!panel.classList.contains('open'));`
- `dashboard.php`:609 — `a.addEventListener('click', function () { setOpen(false); });`
- `dashboard.php`:624 — `if (e.key === 'Escape') setOpen(false);`
- `record.php`:2136 — `setOpen(!mPanel.classList.contains('open'));`
- `record.php`:2139 — `a.addEventListener('click', () => setOpen(false));`
- `record.php`:2142 — `if (e.key === 'Escape') setOpen(false);`
- `records.php`:846 — `setOpen(!panel.classList.contains('open'));`
- `records.php`:852 — `setOpen(false);`
- `records.php`:881 — `setOpen(false);`
- `app/src/app/components/pos/StaffDebtorCombobox.tsx`:57 — `setOpen(false);`
- `app/src/app/components/pos/StaffDebtorCombobox.tsx`:77 — `setOpen(false);`
- `app/src/app/components/pos/StaffDebtorCombobox.tsx`:84 — `setOpen(false);`
- `app/src/app/components/pos/StaffDebtorCombobox.tsx`:95 — `setOpen(true);`
- `app/src/app/components/pos/StaffDebtorCombobox.tsx`:102 — `onFocus={() => setOpen(true)}`
- `app/src/app/components/requests/EditRequestsSection.tsx`:115 — `setOpen(true);`
- `app/src/app/components/requests/RequestsHubSection.tsx`:54 — `onClick={() => setOpen(!open)}`
- `app/src/app/components/suppliers/SupplierCombobox.tsx`:34 — `setOpen(false);`
- `app/src/app/components/suppliers/SupplierCombobox.tsx`:63 — `setOpen(false);`
- `app/src/app/components/suppliers/SupplierCombobox.tsx`:74 — `setOpen(true);`
- `app/src/app/components/suppliers/SupplierCombobox.tsx`:79 — `onFocus={() => setOpen(true)}`
- `app/src/app/components/ui/sidebar.tsx`:93 — `return isMobile ? setOpenMobile((open) => !open) : setOpen((open) => !open);`


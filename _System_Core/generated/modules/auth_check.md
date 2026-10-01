---
title: auth_check
source: includes/auth_check.php
generated: 2026-09-30T09:41:50+02:00
type: module
function_count: 14
---


# auth_check

> Source: `includes/auth_check.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## ims_session_start {#ims_session_start}

**Callers (65):**

- `api/v1/_bootstrap.php`:53 — `ims_session_start();`
- `auth/login.php`:7 — `ims_session_start();`
- `dept/beginning_inventory.php`:8 — `ims_session_start();`
- `dept/products.php`:8 — `ims_session_start();`
- `dept/stock_entries.php`:8 — `ims_session_start();`
- `exec/edit_requests.php`:9 — `ims_session_start();`
- `exec/staff_management.php`:9 — `ims_session_start();`
- `includes/api_tokens.php`:169 — `ims_session_start();`
- `includes/auth_check.php`:32 — `ims_session_start();`
- `includes/auth_check.php`:49 — `ims_session_start();`
- `includes/auth_check.php`:87 — `ims_session_start();`
- `includes/auth_check.php`:132 — `ims_session_start();`
- `includes/auth_check.php`:145 — `ims_session_start();`
- `includes/auth_check.php`:161 — `ims_session_start();`
- `includes/courier_soa_reconcile.php`:172 — `ims_session_start();`
- `includes/courier_soa_reconcile.php`:223 — `ims_session_start();`
- `includes/courier_soa_reconcile.php`:244 — `ims_session_start();`
- `includes/courier_soa_reconcile.php`:560 — `ims_session_start();`
- `includes/courier_soa_reconcile.php`:577 — `ims_session_start();`
- `includes/historical_orders_import.php`:119 — `ims_session_start();`
- `includes/historical_orders_import.php`:159 — `ims_session_start();`
- `includes/historical_orders_import.php`:179 — `ims_session_start();`
- `includes/historical_orders_import.php`:537 — `ims_session_start();`
- `includes/historical_orders_import.php`:616 — `ims_session_start();`
- `includes/historical_orders_import.php`:748 — `ims_session_start();`
- _… and 40 more (run impact.php for full list)_

## ims_current_user {#ims_current_user}

**Callers (49):**

- `api/v1/_bootstrap.php`:145 — `$u = ims_current_user();`
- `api/v1/auth.php`:72 — `$user = ims_current_user();`
- `api/v1/auth.php`:117 — `$user = ims_current_user();`
- `api/v1/deliveries.php`:13 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:21 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:84 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:99 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:121 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:142 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:169 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:348 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:382 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:407 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:430 — `$user = ims_current_user();`
- `api/v1/online_orders.php`:455 — `$user = ims_current_user();`
- `api/v1/order_remarks.php`:30 — `$user = ims_current_user();`
- `api/v1/parcels.php`:10 — `$user = ims_current_user();`
- `api/v1/pdf_to_excel.php`:29 — `$user = ims_current_user();`
- `api/v1/pos.php`:54 — `$user = ims_current_user();`
- `api/v1/sku_barcodes.php`:34 — `$user = ims_current_user();`
- `api/v1/sku_barcodes.php`:60 — `$user = ims_current_user();`
- `api/v1/sku_barcodes.php`:86 — `$user = ims_current_user();`
- `api/v1/waybill.php`:36 — `$user = ims_current_user();`
- `auth/login.php`:12 — `$existing = ims_current_user();`
- `auth/login.php`:34 — `$u = ims_current_user();`
- _… and 24 more (run impact.php for full list)_

## ims_pos_authenticated {#ims_pos_authenticated}

**Callers (9):**

- `api/v1/ar.php`:52 — `if (ims_pos_authenticated()) {`
- `api/v1/auth.php`:53 — `if (ims_pos_authenticated()) {`
- `api/v1/pos.php`:51 — `if (ims_pos_authenticated()) {`
- `auth/login.php`:8 — `if (ims_pos_authenticated()) {`
- `dept/products.php`:17 — `$canDeductStock = !ims_pos_authenticated();`
- `includes/auth_check.php`:164 — `if (!ims_pos_authenticated()) {`
- `includes/pos_unified.php`:18 — `if (ims_pos_authenticated()) {`
- `index.php`:8 — `if (ims_pos_authenticated()) {`
- `login.php`:9 — `if (ims_pos_authenticated()) {`

## ims_login_user {#ims_login_user}

**Tables:** `users`, `roles`

**Callers (3):**

- `api/v1/auth.php`:114 — `if (!ims_login_user($username, $password)) {`
- `auth/login.php`:33 — `if (ims_login_user($username, $password)) {`
- `login.php`:34 — `if (ims_login_user($user, $pass)) {`

## ims_login_pos {#ims_login_pos}

**Tables:** `pos_pins`

**Callers (3):**

- `api/v1/auth.php`:149 — `if (!ims_login_pos($pin)) {`
- `api/v1/pos.php`:18 — `* Walk-in POS terminal API. Requires a POS PIN session (`ims_login_pos()`)`
- `auth/login.php`:25 — `if (ims_login_pos($pin)) {`

## ims_logout {#ims_logout}

**Callers (2):**

- `api/v1/auth.php`:277 — `ims_logout();`
- `auth/logout.php`:7 — `ims_logout();`

## requireRole {#requirerole}

**Callers (12):**

- `auth/settings.php`:6 — `requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], 'login.php');`
- `dept/beginning_inventory.php`:6 — `requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL], '../auth/login.php');`
- `dept/example_protected.php`:6 — `requireRole([IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');`
- `dept/example_protected.php`:18 — `<p>This page is accessible only to TL/Staff roles through <code>requireRole()</code>.</p>`
- `dept/home.php`:6 — `requireRole([IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');`
- `dept/products.php`:6 — `requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');`
- `dept/stock_entries.php`:6 — `requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');`
- `exec/dashboard.php`:7 — `requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');`
- `exec/edit_requests.php`:7 — `requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');`
- `exec/staff_management.php`:7 — `requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');`
- `pos/index.php`:6 — `requireRole([IMS_ROLE_POS], '../auth/login.php');`
- `prodmgr/dashboard.php`:6 — `requireRole([IMS_ROLE_PROD_MANAGER], '../auth/login.php');`

## ims_redirect_dashboard_by_role {#ims_redirect_dashboard_by_role}

**Callers (12):**

- `auth/login.php`:9 — `header('Location: ../' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);`
- `auth/login.php`:14 — `header('Location: ../' . ims_redirect_dashboard_by_role($existing['role']), true, 302);`
- `auth/login.php`:26 — `header('Location: ../' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);`
- `auth/login.php`:36 — `header('Location: ../' . ims_redirect_dashboard_by_role($role), true, 302);`
- `auth/settings.php`:49 — `<p><a href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role((string) ($u['role'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a></p>`
- `dept/beginning_inventory.php`:161 — `<a class="btn" href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role($role), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>`
- `dept/products.php`:730 — `<a class="btn" href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role($role), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>`
- `index.php`:9 — `header('Location: ' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);`
- `index.php`:13 — `header('Location: ' . ims_redirect_dashboard_by_role((string) ims_current_user()['role']), true, 302);`
- `login.php`:10 — `header('Location: ' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);`
- `login.php`:15 — `header('Location: ' . ims_redirect_dashboard_by_role((string) $imsUser['role']), true, 302);`
- `login.php`:37 — `header('Location: ' . ims_redirect_dashboard_by_role($role), true, 302);`

## ims_is_executive {#ims_is_executive}

**Callers (10):**

- `dept/products.php`:15 — `$canManageProducts = ims_is_executive($role) || ims_is_prod_manager($role) || ims_is_tl($role);`
- `dept/products.php`:16 — `$canAddStocks = ims_is_executive($role);`
- `dept/products.php`:18 — `$showPricing = ims_is_executive($role);`
- `dept/products.php`:19 — `$canEditPricing = ims_is_executive($role);`
- `dept/stock_entries.php`:15 — `$canAddStockEntries = ims_is_executive($role);`
- `dept/stock_entries.php`:16 — `$canUseSupplierBatch = ims_is_executive($role);`
- `dept/stock_entries.php`:17 — `$canUseSupplierIdentityAndPrice = ims_is_executive($role);`
- `dept/stock_entries.php`:364 — `<?php if (ims_is_executive($role)): ?>`
- `includes/auth_check.php`:216 — `return ims_is_executive($role) || ims_is_prod_manager($role);`
- `includes/pos_mobile_staff.php`:55 — `if (ims_is_executive($role)) {`

## ims_is_prod_manager {#ims_is_prod_manager}

**Callers (2):**

- `dept/products.php`:15 — `$canManageProducts = ims_is_executive($role) || ims_is_prod_manager($role) || ims_is_tl($role);`
- `includes/auth_check.php`:216 — `return ims_is_executive($role) || ims_is_prod_manager($role);`

## ims_is_tl {#ims_is_tl}

**Callers (5):**

- `dept/beginning_inventory.php`:15 — `$canSet = ims_is_tl($role);`
- `dept/products.php`:15 — `$canManageProducts = ims_is_executive($role) || ims_is_prod_manager($role) || ims_is_tl($role);`
- `dept/products.php`:665 — `} elseif (ims_is_tl($role)) {`
- `dept/stock_entries.php`:18 — `$canRequestEdits = ims_is_tl($role) || ims_is_staff($role);`
- `dept/stock_entries.php`:273 — `} elseif (ims_is_tl($role)) {`

## ims_is_staff {#ims_is_staff}

**Callers (5):**

- `dept/products.php`:662 — `if (ims_is_staff($role)) {`
- `dept/stock_entries.php`:18 — `$canRequestEdits = ims_is_tl($role) || ims_is_staff($role);`
- `dept/stock_entries.php`:186 — `if (ims_is_staff($role)) {`
- `dept/stock_entries.php`:192 — `if (ims_is_staff($role)) {`
- `dept/stock_entries.php`:270 — `if (ims_is_staff($role)) {`

## ims_can_view_all_departments {#ims_can_view_all_departments}

**Callers (3):**

- `dept/beginning_inventory.php`:14 — `$canViewAll = ims_can_view_all_departments($role);`
- `dept/products.php`:14 — `$canViewAll = ims_can_view_all_departments($role);`
- `dept/stock_entries.php`:14 — `$canViewAll = ims_can_view_all_departments($role);`

## ims_change_password {#ims_change_password}

**Tables:** `users`

**Callers (2):**

- `api/v1/auth.php`:289 — `$result = ims_change_password((int) $user['id'], $current, $next);`
- `auth/settings.php`:19 — `$res = ims_change_password((int) ($u['id'] ?? 0), $current, $next);`


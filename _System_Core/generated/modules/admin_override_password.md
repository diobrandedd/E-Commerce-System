---
title: admin_override_password
source: includes/admin_override_password.php
generated: 2026-11-13T04:02:26+01:00
type: module
function_count: 4
---


# admin_override_password

> Source: `includes/admin_override_password.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/system_activity_log.php`

## admin_override_password_configured {#admin_override_password_configured}

**Callers (3):**

- `api/v1/settings.php`:409 — `'configured' => admin_override_password_configured(ims_pdo()),`
- `includes/admin_override_password.php`:40 — `if (admin_override_password_configured($pdo)) {`
- `includes/admin_override_password.php`:74 — `if (!admin_override_password_configured($pdo)) {`

## admin_override_password_verify {#admin_override_password_verify}

**Callers (2):**

- `includes/admin_override_password.php`:41 — `if ($currentPlain === null || $currentPlain === '' || !admin_override_password_verify($pdo, $currentPlain)) {`
- `includes/admin_override_password.php`:84 — `if (!admin_override_password_verify($pdo, $plain)) {`

## admin_override_password_set {#admin_override_password_set}

**Callers (1):**

- `api/v1/settings.php`:428 — `admin_override_password_set(`

## api_require_admin_override_password {#api_require_admin_override_password}

**Callers (3):**

- `api/v1/honey.php`:1573 — `api_require_admin_override_password($input);`
- `api/v1/inventory_check.php`:419 — `api_require_admin_override_password($in);`
- `api/v1/inventory_check.php`:445 — `api_require_admin_override_password($in);`


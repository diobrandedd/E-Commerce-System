---
title: admin_override_password
source: includes/admin_override_password.php
generated: 2026-09-12T11:23:47+02:00
type: module
function_count: 4
---


# admin_override_password

> Source: `includes/admin_override_password.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`

## admin_override_password_configured {#admin_override_password_configured}

**Callers (3):**

- `api/v1/settings.php`:332 — `'configured' => admin_override_password_configured(ims_pdo()),`
- `includes/admin_override_password.php`:40 — `if (admin_override_password_configured($pdo)) {`
- `includes/admin_override_password.php`:61 — `if (!admin_override_password_configured($pdo)) {`

## admin_override_password_verify {#admin_override_password_verify}

**Callers (2):**

- `includes/admin_override_password.php`:41 — `if ($currentPlain === null || $currentPlain === '' || !admin_override_password_verify($pdo, $currentPlain)) {`
- `includes/admin_override_password.php`:71 — `if (!admin_override_password_verify($pdo, $plain)) {`

## admin_override_password_set {#admin_override_password_set}

**Callers (1):**

- `api/v1/settings.php`:351 — `admin_override_password_set(`

## api_require_admin_override_password {#api_require_admin_override_password}

**Callers (9):**

- `api/v1/honey.php`:277 — `api_require_admin_override_password($input);`
- `api/v1/honey.php`:822 — `api_require_admin_override_password($input);`
- `api/v1/honey.php`:1438 — `api_require_admin_override_password($input);`
- `api/v1/honey.php`:1466 — `api_require_admin_override_password($input);`
- `api/v1/honey.php`:1494 — `api_require_admin_override_password($input);`
- `api/v1/materials.php`:531 — `api_require_admin_override_password($input);`
- `api/v1/materials.php`:589 — `api_require_admin_override_password($input);`
- `api/v1/rts.php`:452 — `api_require_admin_override_password($input);`
- `api/v1/rts.php`:482 — `api_require_admin_override_password($input);`


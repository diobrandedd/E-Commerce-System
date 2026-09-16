---
title: auth
source: includes/auth.php
generated: 2026-09-16T03:38:42+02:00
type: module
function_count: 22
---


# auth

> Source: `includes/auth.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## rts_user_role_values {#rts_user_role_values}

**Callers (1):**

- `includes/auth.php`:99 — `return in_array($role, rts_user_role_values(), true);`

## rts_user_role_labels {#rts_user_role_labels}

**Callers (2):**

- `executive.php`:12 — `$roleLabels = rts_user_role_labels();`
- `portal.php`:9 — `$labelsRole = rts_user_role_labels();`

## rts_user_status_values {#rts_user_status_values}

**Callers (1):**

- `includes/auth.php`:104 — `return in_array($status, rts_user_status_values(), true);`

## rts_user_status_labels {#rts_user_status_labels}

**Callers (1):**

- `executive.php`:13 — `$statusLabels = rts_user_status_labels();`

## rts_department_values {#rts_department_values}

**Callers (1):**

- `includes/auth.php`:109 — `return in_array($department, rts_department_values(), true);`

## rts_department_labels {#rts_department_labels}

**Callers (2):**

- `executive.php`:14 — `$deptLabels = rts_department_labels();`
- `portal.php`:10 — `$labelsDept = rts_department_labels();`

## rts_role_valid {#rts_role_valid}

**Callers (1):**

- `includes/auth.php`:266 — `if (!rts_role_valid($role)) {`

## rts_status_valid {#rts_status_valid}

**Callers (4):**

- `includes/auth.php`:227 — `if (!rts_status_valid($status)) {`
- `includes/auth.php`:270 — `if (!rts_status_valid($status)) {`
- `executive.php`:85 — `if (!rts_status_valid($status)) {`
- `executive.php`:136 — `if (!rts_status_valid($status)) {`

## rts_department_valid {#rts_department_valid}

**Callers (4):**

- `includes/auth.php`:231 — `if ($department !== '' && !rts_department_valid($department)) {`
- `includes/auth.php`:286 — `if ($department !== '' && !rts_department_valid($department)) {`
- `executive.php`:89 — `if (!rts_department_valid($department)) {`
- `executive.php`:140 — `if (!rts_department_valid($department)) {`

## rts_role_is_executive {#rts_role_is_executive}

**Callers (5):**

- `includes/admin_nav.php`:23 — `if ($u !== null && rts_role_is_executive((string) ($u['role'] ?? ''))) {`
- `includes/auth.php`:119 — `return rts_role_is_executive($role) || $role === RTS_ROLE_PRODUCTION_MANAGER;`
- `includes/auth.php`:279 — `if ($requiredRole === RTS_ROLE_EXECUTIVE && !rts_role_is_executive($role)) {`
- `includes/auth.php`:350 — `if (in_array(RTS_ROLE_EXECUTIVE, $roles, true) && rts_role_is_executive($u['role'])) {`
- `includes/auth.php`:362 — `return $u !== null && rts_role_is_executive((string) $u['role']);`

## rts_role_can_access_rts_admin {#rts_role_can_access_rts_admin}

**Callers (3):**

- `includes/auth.php`:127 — `return rts_role_can_access_rts_admin((string) ($user['role'] ?? ''));`
- `includes/auth.php`:276 — `if ($requiredRole === RTS_ROLE_ADMIN && !rts_role_can_access_rts_admin($role)) {`
- `includes/auth.php`:347 — `if (in_array(RTS_ROLE_ADMIN, $roles, true) && rts_role_can_access_rts_admin($u['role'])) {`

## rts_can_access_rts_admin {#rts_can_access_rts_admin}

**Callers (6):**

- `api/case.php`:13 — `if (!rts_can_access_rts_admin($authUser)) {`
- `api/delivery_batch_detail.php`:12 — `if (!rts_can_access_rts_admin($u)) {`
- `api/delivery_match_tracking.php`:17 — `if (!rts_can_access_rts_admin($u)) {`
- `api/delivery_resolve.php`:19 — `if (!rts_can_access_rts_admin($u)) {`
- `api/scan_qr_image.php`:17 — `if (!rts_can_access_rts_admin($authUser)) {`
- `api/upload_documentation.php`:11 — `if (!rts_can_access_rts_admin($authUser)) {`

## rts_login_redirect_by_role {#rts_login_redirect_by_role}

**Callers (3):**

- `index.php`:17 — `header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);`
- `login.php`:19 — `header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);`
- `login.php`:31 — `header('Location: ' . rts_login_redirect_by_role($role), true, 302);`

## rts_app_users_has_status_column {#rts_app_users_has_status_column}

**Tables:** `app_users`

**Callers (1):**

- `includes/auth.php`:183 — `$filterActive = rts_app_users_has_status_column($pdo);`

## rts_delivery_submitter_user_id {#rts_delivery_submitter_user_id}

**Tables:** `app_users`

**Callers (2):**

- `api/delivery_bulk.php`:98 — `$submitterId = rts_delivery_submitter_user_id($pdo);`
- `includes/deliveries_upload.php`:229 — `return rts_delivery_submitter_user_id($pdo);`

## rts_session_start {#rts_session_start}

**Callers (11):**

- `api/case.php`:11 — `rts_session_start();`
- `api/delivery_batch_detail.php`:10 — `rts_session_start();`
- `api/delivery_bulk.php`:35 — `rts_session_start();`
- `api/delivery_match_tracking.php`:15 — `rts_session_start();`
- `api/delivery_resolve.php`:17 — `rts_session_start();`
- `api/scan_qr_image.php`:15 — `rts_session_start();`
- `api/upload_documentation.php`:9 — `rts_session_start();`
- `includes/auth.php`:221 — `rts_session_start();`
- `includes/auth.php`:289 — `rts_session_start();`
- `includes/auth.php`:327 — `rts_session_start();`
- `executive.php`:9 — `rts_session_start();`

## rts_auth_user {#rts_auth_user}

**Callers (20):**

- `api/case.php`:12 — `$authUser = rts_auth_user();`
- `api/delivery_batch_detail.php`:11 — `$u = rts_auth_user();`
- `api/delivery_bulk.php`:36 — `$authUser = rts_auth_user();`
- `api/delivery_match_tracking.php`:16 — `$u = rts_auth_user();`
- `api/delivery_resolve.php`:18 — `$u = rts_auth_user();`
- `api/scan_qr_image.php`:16 — `$authUser = rts_auth_user();`
- `api/upload_documentation.php`:10 — `$authUser = rts_auth_user();`
- `includes/admin_nav.php`:22 — `$u = rts_auth_user();`
- `includes/auth.php`:166 — `$auth = rts_auth_user();`
- `includes/auth.php`:342 — `$u = rts_auth_user();`
- `includes/auth.php`:361 — `$u = rts_auth_user();`
- `sp_sort/bootstrap.php`:26 — `$u = rts_auth_user();`
- `executive.php`:10 — `$auth = rts_auth_user();`
- `index.php`:16 — `if (rts_auth_user() !== null) {`
- `index.php`:17 — `header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);`
- `login.php`:18 — `if (rts_auth_user() !== null) {`
- `login.php`:19 — `header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);`
- `login.php`:29 — `$auth = rts_auth_user();`
- `portal.php`:8 — `$u = rts_auth_user();`
- `settings.php`:17 — `$user = rts_auth_user();`

## rts_auth_login {#rts_auth_login}

**Tables:** `app_users`

**Callers (1):**

- `login.php`:28 — `if (rts_auth_login($user, $pass, '')) {`

## rts_auth_verify_user_password {#rts_auth_verify_user_password}

**Tables:** `app_users`

**Callers (1):**

- `api/delivery_resolve.php`:72 — `if (!rts_auth_verify_user_password((int) $u['id'], $password)) {`

## rts_auth_logout {#rts_auth_logout}

**Callers (2):**

- `deliveries/logout.php`:7 — `rts_auth_logout();`
- `logout.php`:7 — `rts_auth_logout();`

## rts_require_roles {#rts_require_roles}

**Callers (7):**

- `sp_sort/bootstrap.php`:13 — `rts_require_roles([RTS_ROLE_ADMIN], '../login.php');`
- `dashboard.php`:12 — `rts_require_roles([RTS_ROLE_ADMIN], 'login.php');`
- `executive.php`:8 — `rts_require_roles([RTS_ROLE_EXECUTIVE], 'login.php');`
- `portal.php`:6 — `rts_require_roles([RTS_ROLE_TEAM_LEADER, RTS_ROLE_STAFF], 'login.php');`
- `record.php`:11 — `rts_require_roles([RTS_ROLE_ADMIN], 'login.php');`
- `records.php`:19 — `rts_require_roles([RTS_ROLE_ADMIN], 'login.php');`
- `settings.php`:8 — `rts_require_roles([`

## rts_is_admin {#rts_is_admin}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._


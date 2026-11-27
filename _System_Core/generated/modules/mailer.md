---
title: mailer
source: includes/mailer.php
generated: 2026-11-27T06:41:00+01:00
type: module
function_count: 5
---


# mailer

> Source: `includes/mailer.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`

## app_mail_settings_public {#app_mail_settings_public}

**Callers (3):**

- `api/v1/settings.php`:455 — `api_ok(app_mail_settings_public(ims_pdo()));`
- `includes/mailer.php`:55 — `return app_mail_settings_public($pdo);`
- `includes/mailer.php`:60 — `$pub = app_mail_settings_public($pdo);`

## app_mail_settings_save {#app_mail_settings_save}

**Callers (1):**

- `api/v1/settings.php`:472 — `$public = app_mail_settings_save(`

## app_mail_configured {#app_mail_configured}

**Callers (1):**

- `includes/mailer.php`:96 — `if (!app_mail_configured($pdo)) {`

## app_mail_spa_base_url {#app_mail_spa_base_url}

**Callers (1):**

- `includes/override_approvals.php`:215 — `$link = app_mail_spa_base_url($pdo) . '/approvals?id=' . $id;`

## app_mail_send {#app_mail_send}

**Callers (2):**

- `api/v1/settings.php`:498 — `$result = app_mail_send(`
- `includes/override_approvals.php`:243 — `$mail = app_mail_send($pdo, $hr['email'], $subject, $html, $text);`


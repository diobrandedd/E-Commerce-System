---
title: forwarding
source: includes/forwarding.php
generated: 2026-09-10T07:09:38+02:00
type: module
function_count: 15
---


# forwarding

> Source: `includes/forwarding.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_forwarding_config {#rts_forwarding_config}

**Callers (7):**

- `includes/forwarding.php`:33 — `return (bool) (rts_forwarding_config()['enabled'] ?? false);`
- `includes/forwarding.php`:43 — `$raw = trim((string) (rts_forwarding_config()['public_url'] ?? ''));`
- `includes/forwarding.php`:78 — `return (bool) (rts_forwarding_config()['ngrok_skip_browser_warning'] ?? true);`
- `includes/forwarding.php`:151 — `(bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true)`
- `includes/forwarding.php`:193 — `$allowLocal = (bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true);`
- `includes/forwarding.php`:241 — `'label' => (string) (rts_forwarding_config()['label'] ?? ''),`
- `includes/forwarding.php`:242 — `'allow_localhost_without_redirect' => (bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true),`

## rts_forwarding_enabled {#rts_forwarding_enabled}

**Callers (5):**

- `includes/forwarding.php`:74 — `if (!rts_forwarding_enabled()) {`
- `includes/forwarding.php`:124 — `if (!rts_forwarding_enabled()) {`
- `includes/forwarding.php`:186 — `if (!rts_forwarding_enabled()) {`
- `includes/forwarding.php`:224 — `if (rts_forwarding_enabled() && rts_forwarding_public_url() !== '') {`
- `includes/forwarding.php`:238 — `'enabled' => rts_forwarding_enabled(),`

## rts_forwarding_public_url {#rts_forwarding_public_url}

**Callers (6):**

- `includes/forwarding.php`:63 — `$url = rts_forwarding_public_url();`
- `includes/forwarding.php`:127 — `if (rts_forwarding_public_url() === '') {`
- `includes/forwarding.php`:170 — `$base = rts_forwarding_public_url();`
- `includes/forwarding.php`:224 — `if (rts_forwarding_enabled() && rts_forwarding_public_url() !== '') {`
- `includes/forwarding.php`:225 — `return rts_forwarding_public_url() . $fullPath;`
- `includes/forwarding.php`:239 — `'public_url' => rts_forwarding_public_url(),`

## rts_forwarding_host {#rts_forwarding_host}

**Callers (3):**

- `includes/forwarding.php`:103 — `$want = strtolower(rts_forwarding_host());`
- `includes/forwarding.php`:189 — `$host = rts_forwarding_host();`
- `includes/forwarding.php`:240 — `'host' => rts_forwarding_host(),`

## rts_forwarding_ngrok_skip_warning {#rts_forwarding_ngrok_skip_warning}

**Callers (1):**

- `includes/forwarding.php`:84 — `if (!rts_forwarding_ngrok_skip_warning()) {`

## rts_forwarding_fetch_headers {#rts_forwarding_fetch_headers}

**Callers (2):**

- `includes/forwarding.php`:93 — `return json_encode(rts_forwarding_fetch_headers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';`
- `includes/forwarding.php`:243 — `'fetch_headers' => rts_forwarding_fetch_headers(),`

## rts_forwarding_fetch_headers_json {#rts_forwarding_fetch_headers_json}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_forwarding_current_host {#rts_forwarding_current_host}

**Callers (2):**

- `includes/forwarding.php`:104 — `$have = rts_forwarding_current_host();`
- `includes/forwarding.php`:145 — `$host = rts_forwarding_current_host();`

## rts_forwarding_current_is_public_host {#rts_forwarding_current_is_public_host}

**Callers (1):**

- `includes/forwarding.php`:130 — `if (rts_forwarding_current_is_public_host()) {`

## rts_forwarding_is_loopback_host {#rts_forwarding_is_loopback_host}

**Callers (1):**

- `includes/forwarding.php`:152 — `&& rts_forwarding_is_loopback_host($host)`

## rts_forwarding_should_redirect_request {#rts_forwarding_should_redirect_request}

**Callers (1):**

- `includes/forwarding.php`:166 — `if (!rts_forwarding_should_redirect_request()) {`

## rts_forwarding_maybe_redirect {#rts_forwarding_maybe_redirect}

**Callers (2):**

- `includes/db.php`:75 — `rts_forwarding_maybe_redirect();`
- `includes/forwarding.php`:182 — `* Prefer rts_forwarding_maybe_redirect() in PHP; this covers edge cases before PHP runs.`

## rts_forwarding_head_script {#rts_forwarding_head_script}

**Callers (1):**

- `record.php`:33 — `<?= rts_forwarding_head_script() ?>`

## rts_app_public_url {#rts_app_public_url}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_forwarding_api_payload {#rts_forwarding_api_payload}

**Callers (5):**

- `api/v1/auth.php`:55 — `'forwarding' => rts_forwarding_api_payload(),`
- `api/v1/auth.php`:75 — `'forwarding' => rts_forwarding_api_payload(),`
- `api/v1/auth.php`:95 — `'forwarding' => rts_forwarding_api_payload(),`
- `api/v1/auth.php`:133 — `'forwarding' => rts_forwarding_api_payload(),`
- `api/v1/auth.php`:153 — `'forwarding' => rts_forwarding_api_payload(),`


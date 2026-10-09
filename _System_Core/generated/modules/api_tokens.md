---
title: api_tokens
source: includes/api_tokens.php
generated: 2026-10-09T06:31:39+02:00
type: module
function_count: 8
---


# api_tokens

> Source: `includes/api_tokens.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/rts_schema.php`

## pos_api_tokens_ensure_schema {#pos_api_tokens_ensure_schema}

**Callers (3):**

- `includes/api_tokens.php`:49 — `pos_api_tokens_ensure_schema($pdo);`
- `includes/api_tokens.php`:81 — `pos_api_tokens_ensure_schema($pdo);`
- `includes/api_tokens.php`:96 — `pos_api_tokens_ensure_schema($pdo);`

## pos_api_token_hash {#pos_api_token_hash}

**Callers (3):**

- `includes/api_tokens.php`:51 — `$hash = pos_api_token_hash($plain);`
- `includes/api_tokens.php`:85 — `)->execute([pos_api_token_hash($plain)]);`
- `includes/api_tokens.php`:103 — `$st->execute([pos_api_token_hash($plain)]);`

## pos_api_token_issue {#pos_api_token_issue}

**Tables:** `pos_api_tokens`

**Callers (2):**

- `api/v1/auth.php`:176 — `$issued = pos_api_token_issue(ims_pdo(), [`
- `api/v1/auth.php`:254 — `$issued = pos_api_token_issue(ims_pdo(), [`

## pos_api_token_revoke_plain {#pos_api_token_revoke_plain}

**Tables:** `pos_api_tokens`

**Callers (1):**

- `api/v1/auth.php`:319 — `pos_api_token_revoke_plain(ims_pdo(), $plain);`

## pos_api_token_find {#pos_api_token_find}

**Tables:** `pos_api_tokens`

**Callers (1):**

- `includes/api_tokens.php`:161 — `$row = pos_api_token_find($pdo, $plain);`

## pos_api_bearer_from_request {#pos_api_bearer_from_request}

**Callers (2):**

- `api/v1/auth.php`:316 — `$plain = (string) ($GLOBALS['api_bearer_token'] ?? pos_api_bearer_from_request());`
- `includes/api_tokens.php`:155 — `$plain = pos_api_bearer_from_request();`

## pos_api_apply_bearer_auth {#pos_api_apply_bearer_auth}

**Tables:** `users`, `roles`

**Callers (1):**

- `api/v1/_bootstrap.php`:57 — `pos_api_apply_bearer_auth();`

## pos_sales_ensure_refund_columns {#pos_sales_ensure_refund_columns}

**Tables:** `pos_sales`

**Callers (3):**

- `api/v1/pos.php`:545 — `pos_sales_ensure_refund_columns(ims_pdo());`
- `api/v1/pos.php`:579 — `pos_sales_ensure_refund_columns(ims_pdo());`
- `includes/pos_refund.php`:60 — `pos_sales_ensure_refund_columns($pdo);`


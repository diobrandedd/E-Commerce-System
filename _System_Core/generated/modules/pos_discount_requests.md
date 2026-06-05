---
title: pos_discount_requests
source: includes/pos_discount_requests.php
generated: 2026-06-05T10:39:45+02:00
type: module
function_count: 7
---


# pos_discount_requests

> Source: `includes/pos_discount_requests.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_pos.php`

## pos_discount_quote {#pos_discount_quote}

**Callers (1):**

- `includes/pos_discount_requests.php`:97 — `$quote = pos_discount_quote($pdo, $items, $discountPercent);`

## pos_discount_request_create {#pos_discount_request_create}

**Tables:** `pos_discount_requests`

**Callers (1):**

- `api/v1/pos.php`:197 — `$result = pos_discount_request_create(`

## pos_discount_request_approve {#pos_discount_request_approve}

**Tables:** `pos_discount_requests`

**Callers (1):**

- `api/v1/pos_discounts.php`:44 — `$payload = pos_discount_request_approve(ims_pdo(), $id, (int) $user['id']);`

## pos_discount_request_decline {#pos_discount_request_decline}

**Tables:** `pos_discount_requests`

**Callers (1):**

- `api/v1/pos_discounts.php`:62 — `pos_discount_request_decline(ims_pdo(), $id, (int) $user['id'], $note !== '' ? $note : null);`

## pos_discount_request_fetch_for_pin {#pos_discount_request_fetch_for_pin}

**Tables:** `pos_discount_requests`

**Callers (1):**

- `api/v1/pos.php`:224 — `$result = pos_discount_request_fetch_for_pin(ims_pdo(), $requestId, $pinId);`

## pos_discount_receipt_payload {#pos_discount_receipt_payload}

**Tables:** `pos_sales`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/pos_discount_requests.php`:230 — `$payload['receipt'] = pos_discount_receipt_payload($pdo, (int) $row['pos_sale_id']);`

## pos_discount_request_list {#pos_discount_request_list}

**Tables:** `pos_discount_requests`, `pos_pins`, `users`

**Callers (1):**

- `api/v1/pos_discounts.php`:27 — `$payload = pos_discount_request_list(ims_pdo(), $status, $limit);`


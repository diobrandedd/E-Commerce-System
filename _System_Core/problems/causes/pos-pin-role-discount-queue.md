---
type: cause
tags: [cause]
---

# Cause: POS gated to PIN role and discount approval queue

## What went wrong

`/pos` required SPA role `pos`. IMS staff and executives were blocked (or used a PIN terminal). 10%/20% buttons created `pos_discount_requests` and waited for executive approve, so cashiers thought “no request was made” when they were not on a PIN session or the queue never received a row. Walk-in receipts stored `sold_by_pin_id` only, so Sales could not show the staff account that sold.

## Where in code

- `RequireAuth` on `/pos` with `roles: ["pos"]` in `app/src/app/routes.ts`
- `api_pos_require_session()` required `ims_pos_authenticated()`
- `POSUnifiedPage` `submitDiscountRequest` → `discount_request_create`

## Related problems

- [[records/2026-08-18_pos-account-discount-sales-log]]

## Prevent

Keep PIN and IMS cashiers on the same checkout path. Probe `sold_by_user_id` before joining `users` — [[003_Fragile_Edges#Runtime schema probes]]

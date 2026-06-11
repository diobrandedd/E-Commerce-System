---
status: approved
created: 2026-06-12
area: supplies-inventory
tags: [supplies, hr, lot, wac, requests]
---

# Supplies — HR pool, lot stock-in, executive-approved issues

## Summary

Org-wide supplies catalog (no per-department items). HR stocks in purchase lots (total ₱ + qty → WAC per pc). Staff request offline; HR submits **issue** requests with **requester name**; executive approves on Requests page.

## Roles

| Action | Executive | HR | Prod manager |
|--------|-----------|-----|--------------|
| View supplies | yes | yes | read-only |
| Create catalog | yes | yes | no |
| Stock-in lot | yes | yes | no |
| Set on-hand direct | yes | no | no |
| Submit issue request | no | yes | no |
| Submit count fix (`set_on_hand`) | no | yes | no |
| Approve/reject | yes | no | no |

## Workflow

1. Executive or HR creates supply SKU (name + unit only).
2. HR stock-in lot → `on_hand` += qty, `current_price` = WAC.
3. Staff asks HR offline → HR submits issue (requester name, qty, reason).
4. Executive approves → `on_hand` − qty, audit `issue: {name} — {reason}`.

## Out of scope v1

Staff self-service portal; supply lines in product costing; low-stock alerts.

## Links

- [[fixes/supplies-hr-lot-inventory]]
- [[problems/records/2026-06-12_supplies-hr-lot-inventory]]

---
status: approved
created: 2026-07-14
area: rts
tags: [rts, pos, inventory, use-rts]
---

# Use RTS + POS stock source

## Summary

Prod managers **Use RTS** on sellable RTS tables (deduct `rts_inventory_rows`) and see a **Used RTS** history with **Where used** = Packaging (RTS page) or POS (checkout). POS checkout asks **RTS products** vs **Non-RTS** before deducting RTS sellable vs packaging/dept finished goods. No auto-credit into packaging.

## Roles

| Action | PM | Executive | POS cashier |
|--------|----|-----------|-------------|
| Use RTS | yes | no | no |
| View Used RTS | yes (+ inventory viewers) | yes | no |
| Choose RTS / Non-RTS at checkout | — | — | yes |

## Out of scope

Crediting packaging from RTS; damaged Use RTS; parcel ship from RTS pool.

## Links

- Plan: [[plans/archive/2026-07-14_rts-use-and-pos-source_plan]]
- Record: [[problems/records/2026-07-14_rts-use-and-pos-stock-source]]

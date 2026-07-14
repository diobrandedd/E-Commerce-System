--
status: approved
created: 2026-07-14
area: inventory
tags: [monthly-inventory, executive, edit-log, stocktake]
---

# Monthly inventory — executive create + set + edit logs

## Summary

Executives reconcile physical month-end counts with system stock: **set** absolute quantities and **add** missing lots/rows (supplier → supplier codes) across honey warehouse/production/packaging, packaging materials, dried paragis / black rice / cacao / honey comb raw bags, dept finished, and RTS. An **Edit Logs** tab shows audit rows from `inventory_edit_log`.

## Roles

| Action | Executive | Others |
|--------|-----------|--------|
| Open Monthly Inventory page | yes | no |
| Create lot / seed row | yes | no |
| Set counted qty | yes | no |
| View Edit Logs | yes | no |

No new Super Admin role. Day-to-day PM/HR stock-in and request/approve flows stay unchanged.

## Logs scope (v1)

Monthly create/set + existing honey executive edit + materials set-on-hand mirrored into the log. **Not** parcel ship, POS, normal PM stock-in, RTS case sync.

## Out of scope v1

Undo from Logs; system-wide mutation logging; new IMS roles.

## Links

- Plan: [[plans/active/2026-07-14_monthly-inventory-executive_plan]]

---
status: approved
created: 2026-06-24
owner: POS / executive
approved: true
---

# Feature: POS AR checkout + Sales pool integration

Links: [[006_AI_Directed_Engineering]] · [[010_Product_Theory]]

## Problem

Cashiers cannot record Accounts Receivable at the POS register. AR credits from IMS "take on credit" lack debtor name/mobile and do not appear in the executive Sales checkout pool. Executives cannot mark credits unpaid after accidental paid.

## Outcome

- POS honey/dept checkout can record **AR** (full price, inventory deduct) with staff debtor picker or custom name + mobile.
- AR creates `pos_sales` (`is_ar=1`, `AR-RCP-…`) + `pos_receipt_items` + linked `ar_credits`.
- Executive **Sales** page: All checkouts includes AR; new **AR only** pill (red when active); red **AR** type badge.
- `/ar` ledger: debtor/mobile columns; staff see own rows via `debtor_user_id`; executive mark paid **and** unpaid.

## Non-goals

- Discount on AR checkouts
- Dept products on honey Sales variant charts (dept AR still writes `pos_sales`)

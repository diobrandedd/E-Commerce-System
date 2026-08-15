---
date: 2026-08-15
status: approved
area: sales-report
---

# Spec: Sales page — shipped channel wording + dept products

## Problem

1. Pack scan confirm writes `honey_sales_log` rows with `sale_type = 'parcel'`; the Sales page labels
   that channel **Online** and folds it into "total bottles sold". Those units are packed for shipping,
   not sold — the wording misleads executives.
2. Paragis Tea, Black Rice Coffee, Jar Cacao and Honey Comb never appear. The report reads
   `honey_sales_log` only; dept POS writes `pos_sales`/`pos_receipt_items` and dept pack scans write
   `honey_bottle_pack_session_dept_items` + `dept_pouch_units`.

## Decisions (owner, 2026-08-15)

- Parcel units stay inside the headline total; the channel is renamed **Shipped** (scanned in pack scan).
- One combined page — no product picker. All products total together, broken down by product card.
- Dept products report **units only** (no revenue split); honey keeps its peso summary.

## Acceptance

- Channel chips read All / Walk-in / **Shipped**; share line says Walk-in … · Shipped ….
- Headline counts honey bottles + dept pouches/jars for the period; "total units".
- A **By product** card row shows Honey, Paragis Tea, Black Rice Coffee, Jar Cacao, Honey Comb with
  walk-in vs shipped units, including zero rows so staff see the product exists.
- Dept walk-in receipts appear in the checkout list with product names on the expanded lines.
- Honey variant/size grid and peso summary cards are unchanged apart from wording.
- No schema change; dept counts read existing `dept_pouch_units` consumption columns.

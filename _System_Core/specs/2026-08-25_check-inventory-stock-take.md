---
status: approved
created: 2026-08-25
owner: ops
area: inventory
tags: [inventory, stock-take, barcodes, honey, dept]
---

# Check Inventory (physical stock take)

> Theory — scan what is physically on the shelf, then compare it to what the system says.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[plans/active/2026-08-25_check-inventory-stock-take_plan]]

## Problem

There is no way to verify system inventory against the physical shelf. A manager may decide on any day that today is a counting day, but staff have no tool to record what they physically see, and nobody can produce a variance report afterwards.

## Users & roles

| Who | Can |
|-----|-----|
| Every staff user (`inventory.check.scan`) | Open the module and scan **any** finished product into the open count. Sees the scan screen only. |
| Executive / production manager (`inventory.check.view_all`) | Full scanned summary across all inventories, compare, and history. |
| Manager (`inventory.check.manage`) | Open and close the day's count event. |

## Desired outcome (success criteria)

- [ ] Manager opens a count for today; all staff scan into that same count
- [ ] Any user can scan any product line — no per-line restriction
- [ ] Summary shows supplier, batch, bottling date, variant, and per-size counts
- [ ] Clicking a summary row shows each barcode with who scanned it and when
- [ ] Compare produces a variance report of counted vs system inventory
- [ ] Counts and comparison runs are kept as dated history

## Behavior (business rules)

1. **One shared count event per calendar date.** Opening again the same day resumes the open event.
2. **Scanning never changes stock.** The count is observational; corrections stay in Monthly Inventory and edit requests.
3. **A barcode can only be counted once per event** — rescanning returns `duplicate_event`.
4. Recognized codes are recorded even when the system says the unit is not on the shelf; those rows carry a flag (`not_in_stock`, `void`) so the compare can surface them.
5. Unknown codes are rejected and not recorded.
6. **Compare covers only the product lines actually counted.** A count with honey scans only never reports dept shortages.
7. Within a counted line, every system batch with stock is included, so a batch nobody scanned shows as short.
8. Honey variance is per batch and size. Dept system stock is product-level only, so dept variance is per product, with scanned batch rows shown as supporting detail.
9. Closing an event stops further scanning; history and compare remain readable.

## User flow (high level)

1. Manager opens **Inventory → Check inventory** and starts today's count
2. Staff scan finished-product barcodes
3. Manager reviews the summary, drills into a row to see scanners and codes
4. Manager presses **Compare with current inventory** and reads the variance report
5. The count and its comparison runs stay in history by date

## Data & integrity (conceptual)

A count **event** per date owns many **scans** (snapshotting product, batch, bottling date, variant, size at scan time). A **compare run** owns **variance lines** so a report is reproducible even after stock later moves.

## UI/UX intent

- Layout follows the packaging "Scanned into inventory" report: date select, total cards, table, row-click modal
- Scan screen follows the pack scan page (wedge input + camera, live list, remove a mis-scan)
- Staff without `view_all` see only the scan screen

## Out of scope

- Auto-adjusting inventory from variances
- Counting raw materials, packaging materials, or supplies
- Counting units that have no barcode

## Approval to plan

- [x] Spec reviewed by human (chat approval 2026-08-25)
- [x] Ready for implementation

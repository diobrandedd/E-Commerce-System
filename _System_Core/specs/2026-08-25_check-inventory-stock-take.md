---
status: approved
created: 2026-08-25
updated: 2026-09-05
owner: ops
area: inventory
tags: [inventory, stock-take, barcodes, honey, dept, audit]
---

# Check Inventory (physical stock take / audit)

> Theory — record what is physically on the shelf via an audit session, then compare it to what the system says before saving to history.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[plans/archive/2026-08-25_check-inventory-stock-take_plan]]

## Problem

There is no way to verify system inventory against the physical shelf. A manager may decide on any day that today is a counting day, but staff have no tool to record what they physically see, and nobody can produce a variance report afterwards.

## Users & roles

| Who | Can |
|-----|-----|
| Every staff user (`inventory.check.scan`) | Open the module and add finished products into the open audit (Add to list). |
| Executive / production manager (`inventory.check.view_all`) | Full summary, history of closed audits. |
| Manager (`inventory.check.manage`) | Start session; Complete → compare modal; Confirm and save (close) or Go back to edit. |

## Desired outcome (success criteria)

- [x] Manager opens a count for today; staff add lines into that same count
- [x] Add-to-list modal: scan/camera, editable qty, required batch number
- [x] Session list rows remain editable while the event is open
- [x] Complete shows audited vs system inventory in a modal (Go back or Confirm and save)
- [x] Counts and comparison runs are kept as dated history with who/when

## Behavior (business rules)

1. **One shared count event per calendar date.** Opening again the same day resumes the open event.
2. **Scanning never changes stock.** The count is observational; corrections stay in Monthly Inventory and edit requests.
3. **SKU lines** require a **batch number**; upsert identity is event + SKU code + batch. Rescan/add increases qty for that identity.
4. **Unique stickers** are +1 per code per event; submitted batch must match the barcode’s packaging/dept batch.
5. Recognized codes are recorded even when the system says the unit is not on the shelf; those rows carry a flag (`not_in_stock`, `void`).
6. Unknown codes are rejected and not recorded.
7. **Complete** does not close yet — opens a **preview compare** (no DB write). **Go back** keeps the event open. **Confirm and save** freezes a compare run then closes the event.
8. **Compare covers only the product lines actually counted.** Honey variance is per batch and size. Dept system stock is product-level.
9. Closing an event stops further adds; history remains readable.

## User flow (high level)

1. Manager opens **Inventory → Check inventory** and starts today's audit
2. Staff click **Add to list**, scan product, set qty + batch, Confirm
3. Edit or remove rows on the session list as needed
4. Manager presses **Complete** → compare modal (audited vs system)
5. **Go back** to edit, or **Confirm and save** → history

## Data & integrity (conceptual)

A count **event** per date owns many **scans** (snapshotting product, batch, bottling date, variant, size at scan time). A **compare run** owns **variance lines** so a report is reproducible even after stock later moves.

## UI/UX intent

- Primary: session list + Add-to-list `AppModal` + Complete compare `AppModal`
- History tab for closed audits (`view_all`)
- Motion: `AppModal`, `AppSuccessNotice`, `AppErrorBanner`, `Button` press feedback

## Out of scope

- Auto-adjusting inventory from variances
- Counting raw materials, packaging materials, or supplies
- Counting units that have no barcode

## Approval to plan

- [x] Spec reviewed by human (chat approval 2026-08-25; UX refresh 2026-09-05)
- [x] Ready for implementation

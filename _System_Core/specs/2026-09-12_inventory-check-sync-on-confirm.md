---
status: shipped
created: 2026-09-12
owner: ops
area: inventory
tags: [inventory-check, stock-sync, barcodes, audit, admin-password]
---

# Check inventory: Confirm and update inventory (sync to audited)

> Theory — after Audit vs system shows variances, Confirm writes the frozen variance history **and** makes system finished stock match the audited counts (scoped lines only), including unique-barcode cleanup. Requires administrator override password.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[003_Fragile_Edges]] · [[specs/2026-08-25_check-inventory-stock-take]] · [[specs/2026-09-11_inventory-check-scoped-compare]] · [[fixes/monthly-inventory-admin-password]] · [[plans/archive/2026-09-12_inventory-check-sync-on-confirm_plan]] · [[problems/records/2026-09-12_inventory-check-sync-on-confirm]]

## Problem

Check inventory already records physical counts and freezes Audit vs system variance into history, but **Confirm and save does not change stock**. Ops treat the audited total as the correct on-hand. They need Confirm to:

1. Set finished inventory for audited product/size lines to the **audited** qty (+ variance → add, − variance → deduct).
2. Treat unique barcodes scanned in the audit as the only stickers still on the shelf for that scope; void in-stock uniques in scope that were **not** audited.
3. Keep the exact variance snapshot for executive review (already partly shipped).

Today Monthly inventory is the only absolute override path; this adds a second, audit-driven path with the same password gate.

## Users & roles

| Who | Can |
|-----|-----|
| Staff (`inventory.check.scan`) | Scan into own open session (unchanged). **Cannot** apply stock sync. |
| Manager (`inventory.check.manage`) | Open/complete session; open compare modal. Apply sync only with **administrator password**. |
| Full report / executive (`inventory.check.view_all`) | Multi-select closed history → merge compare → **Confirm and update inventory** (password). Review history forever. |

## Desired outcome (success criteria)

- [x] Compare modal CTA is **Confirm and update inventory** (not “save only”).
- [x] On confirm (after admin password): freeze variance history **then** sync scoped finished stock so each line’s system qty = audited qty.
- [x] Unique barcodes present in the audit for a scoped key remain; other **in_stock** uniques for that same key are voided (reason tied to audit apply).
- [x] Unaudited products/sizes are never touched.
- [x] SKU-only lines (no unique codes in the audit for that key): sync aggregate qty only; do **not** mass-void uniques for that key.
- [x] History retains the exact pre-apply variance list; run records that stock was applied (who/when + void counts).
- [x] Merge-selected closed sessions can apply the same sync once (view_all).

## Behavior (business rules)

### Variance → stock

- `variance = audited − system` (unchanged math).
- Apply sets **system finished qty = audited** for each compare line with audited &gt; 0.
- Effect: **+ variance → add**, **− variance → deduct**, **0 → no qty change**.

### Scope

- Same as compare today: only keys with audited &gt; 0 (honey `variant|size`, dept `product_key`).
- Batch / bottling date remain **documentation only** — matching and sync are not per-batch.

### Honey qty apply

- System qty is the **sum** of `honey_packaging_rows.current_*` for that variant + size.
- After apply, that sum must equal audited.
- Implementation may distribute add/deduct across packaging lots (prefer deducting from lots that still have qty; credit adds onto a sensible existing lot). Must write inventory edit logs with source `inventory_check_apply`.

### Dept qty apply

- Set `dept_inventory_rows.current_qty` for that `product_key` to audited (create row if missing). Log source `inventory_check_apply`.

### Unique barcodes

For each scoped key that has **at least one** unique (sticker) code in the audit scan set:

1. Audited unique codes for that key = keepers.
2. Every **in_stock** unique unit for that key whose code ∉ keepers → **void** (honey via existing void path + packaging consistent with apply order; dept equivalent).
3. Void reason: e.g. `inventory_check_apply` + event/run id.

If a scoped key has **zero** unique codes in the audit (SKU-only count): **qty sync only**; do not void uniques for that key.

### Order of operations (single transaction)

1. Verify administrator override password.
2. Build compare snapshot from live system (same as preview).
3. Persist compare run + variance lines (history).
4. Apply barcode voids (scoped, when uniques present).
5. Apply aggregate qty set-to-audited for each line.
6. Mark run as `stock_applied` (+ actor, time, void count).
7. Close open event if this was an open-session finalize.

Preview remains read-only. **Go back** still edits without writing.

### Password & permissions

- Apply always requires `api_require_admin_override_password` (System Settings administrator password).
- Caller must have `inventory.check.manage` (open session) or `inventory.check.view_all` (merge apply).
- If password unset → 403 with Settings guidance (same as Monthly inventory).

### Multi-select merge

- view_all merge preview unchanged.
- New/extended confirm applies sync from **merged** event ids once; stores one compare run (primary `event_id` = first selected; note lists merged ids) with full variance lines.
- Does not reopen closed events.

### Supersedes

Amends [[specs/2026-08-25_check-inventory-stock-take]] “Out of scope: Auto-adjusting inventory” and “Scanning never changes stock” for the **confirm** step only. Scanning itself still never mutates stock.

## User flow (high level)

1. Complete open audit → Audit vs system modal (preview).
2. Enter administrator password → **Confirm and update inventory**.
3. Success: history has variance; stock matches audited for scoped lines; toast confirms apply.
4. Executives reopen history → Comparison tab shows frozen variance + “Stock applied” badge when applicable.
5. Optional: view_all selects several closed audits → merge compare → same confirm + password.

## Data & integrity (conceptual)

- Keep `inventory_check_compare_runs` / `inventory_check_variance_lines` as the immutable pre-apply report.
- Extend compare run with apply metadata (`stock_applied`, `stock_applied_at`, `stock_applied_by`, `barcodes_voided_count`, optional `merged_event_ids`).
- Do not rewrite variance lines after stock moves.

## UI/UX intent

- Modal title stays **Audit vs system inventory**.
- Primary CTA: **Confirm and update inventory** (gold); secondary **Go back**.
- Short warning copy: updates finished stock to audited counts; voids unscanned unique stickers in scope; requires administrator password.
- `AdminOverridePasswordModal` before API apply.
- History detail: show applied state + void count when present.
- Motion: existing `AppModal` / feedback kit.

## Out of scope

- Raw / packaging materials / supplies sync
- Warehouse or production gallon stages
- Creating new unique barcode stickers for positive variance (qty can rise without new stickers)
- Changing Monthly inventory UX
- Auto-apply without password

## Open decisions (locked in plan if approved)

| Topic | Proposal |
|-------|----------|
| Positive variance + no new stickers | Allow aggregate qty &gt; unique in_stock count |
| SKU-only scoped key | Qty sync only; no unique voiding |
| Who may apply | manage (open) / view_all (merge) + admin password |

## Approval to plan

- [x] Spec reviewed by human (chat approval 2026-09-12)
- [x] Ready for implementation

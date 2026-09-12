---
status: approved
spec: "[[specs/2026-09-12_inventory-check-sync-on-confirm]]"
created: 2026-09-12
approved_by: human
approved_date: 2026-09-12
---

# Implementation Plan: Check inventory — sync stock on Confirm

> **AI drafts this** from a spec. Human approves before any code.  
> Spec: [[specs/2026-09-12_inventory-check-sync-on-confirm]]

## Summary

Extend Check inventory finalize so **Confirm and update inventory** (1) freezes Audit vs system variance history, (2) voids in-scope unique barcodes not present in the audit when uniques were scanned, (3) sets honey packaging / dept finished aggregates to audited qty, gated by the System Settings administrator password. Merge-selected closed sessions get the same apply path for `view_all`.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]] — inventory check private sessions, scoped compare, batch docs; Monthly admin password
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]] · `AdminOverridePasswordModal`
- Related: [[fixes/monthly-inventory-admin-password]] · [[includes/admin_override_password.php]]
- Do **not** mutate stock on scan — only on confirm/apply

## Impact preview

| Area | Change |
|------|--------|
| API | `inventory_check.php`: extend `finalize`; add `finalize_merged` (or `apply_sync`); require `admin_password` |
| Includes | `inventory_check.php`: apply helpers; call honey/dept void + qty set; edit-log source |
| DB | migration: apply columns on `inventory_check_compare_runs` (+ optional `merged_event_ids`) |
| SPA | `InventoryCheckPage` CTA + password modal; history badge; `inventoryCheck.ts` client |
| Legacy / Python | none |

Run `impact.php` on: `inventory_check_compare_run`, `inventory_check_event_close`, `honey_bottle_void_code`, packaging aggregate adjust helpers, dept set qty helpers.

## Implementation steps

### Phase 1 — Data / backend

1. [ ] Migration `sql/migration_phase82_inventory_check_stock_apply.sql`:
   - `stock_applied` TINYINT(1) NOT NULL DEFAULT 0
   - `stock_applied_at` DATETIME NULL
   - `stock_applied_by` INT NULL
   - `barcodes_voided_count` INT NOT NULL DEFAULT 0
   - `merged_event_ids` VARCHAR(255) NULL (comma-separated ids when merge apply)
2. [ ] `inventory_check_audited_unique_codes(PDO, eventIds): map key → set of codes` from scans that are unique stickers (not SKU-only lines).
3. [ ] `inventory_check_void_missing_uniques(...)` — for each key with non-empty audited unique set, void in_stock honey/dept units in that key not in set; return void count. Reason includes run/event id.
4. [ ] `inventory_check_apply_qty_to_audited(...)` — for each compare line:
   - Honey: adjust packaging lots until sum(current_size) = counted_qty; log `inventory_check_apply`.
   - Dept: set `current_qty` = counted_qty; log same source.
5. [ ] `inventory_check_finalize_with_sync(PDO, eventIds, userId, input)`:
   - require admin password
   - permission: single open event → manage + access; multi → view_all
   - transaction: compare_run → voids → qty apply → mark run applied → close if single open event
6. [ ] Ensure void-then-set order so final aggregates match audited even if void helper does not decrement packaging.

### Phase 2 — API

1. [ ] `finalize`: accept `admin_password`; call sync finalize (breaking change vs save-only — intentional per spec).
2. [ ] `finalize_merged` POST `{ event_ids: number[], admin_password }` for view_all merge apply.
3. [ ] Return run payload including apply metadata for SPA toast/history.
4. [ ] Keep `compare_preview` / `compare_preview_merged` read-only.

### Phase 3 — Frontend

1. [ ] Rename CTA → **Confirm and update inventory**; warning copy about stock + barcode void + password.
2. [ ] Wire `AdminOverridePasswordModal` before finalize / finalize_merged.
3. [ ] Merge toolbar: same confirm when selection ≥ 1 (or ≥ 2 if product prefers multi-only — default ≥ 1 closed ids already selectable).
4. [ ] History detail / list: badge **Stock applied** when `stock_applied`; show void count.
5. [ ] Success notice: e.g. “Inventory updated to audited counts. Variance saved to history.”

### Phase 4 — Docs & cleanup

1. [ ] Update [[003_Fragile_Edges]] inventory-check bullets (confirm mutates stock; password; SKU-only vs unique void).
2. [ ] Note supersession on [[specs/2026-08-25_check-inventory-stock-take]] out-of-scope line.
3. [ ] Problem record + [[002_Memory_Bank]]; regenerate memory bank.
4. [ ] Move this plan to `plans/archive/` after ship.
5. [ ] Point [[001_System_State]] Active Spec/Plan then clear on archive.

## Files to touch (expected)

| File | Action |
|------|--------|
| `sql/migration_phase82_inventory_check_stock_apply.sql` | create |
| `includes/inventory_check.php` | modify — apply helpers + finalize sync |
| `api/v1/inventory_check.php` | modify — finalize + finalize_merged |
| `includes/honey_bottle_barcodes.php` / dept pouch | modify only if void/list helpers need scoped “in_stock for key” queries |
| `app/src/app/lib/inventoryCheck.ts` | modify |
| `app/src/app/pages/inventory/InventoryCheckPage.tsx` | modify |
| `app/src/app/components/inventory/InventoryCheckHistoryDetailModal.tsx` | modify |
| `app/src/app/components/inventory/AdminOverridePasswordModal.tsx` | reuse |
| `_System_Core/003_Fragile_Edges.md` | modify |
| tests under `tests/inventory/` | extend if practical |

## Test plan

- [ ] Open session: count honey size below system (− variance) → confirm + password → packaging sum = audited; history shows applied.
- [ ] Count above system (+ variance) → packaging sum rises; no new stickers required.
- [ ] Unique stickers A,B audited; C in_stock same variant|size not audited → C voided; A,B remain.
- [ ] SKU-only line (no uniques in audit) → qty sync; existing unscanned uniques for that size **not** voided.
- [ ] Wrong/missing admin password → 403; stock unchanged; event stays open.
- [ ] Go back → no write.
- [ ] view_all merge two closed honey audits → confirm updates once; one run with merged ids; variance frozen.
- [ ] Unaudited variant/size unchanged.
- [ ] Role without manage/view_all cannot call apply.
- [ ] Hard-refresh SPA; run phase 82 SQL on env.

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Void without packaging adjust leaves qty high | Always **set aggregates to audited after voids** in same transaction |
| Mass-void wrong SKUs | Scope strictly to compare keys with audited unique codes |
| Double-apply on closed history | Reject apply if same event_ids already have `stock_applied` run, or allow only with explicit re-apply flag (default: reject duplicate apply for identical merge set) |
| Overlap with Monthly inventory | Same password; edit log source distinguishes `inventory_check_apply` vs `monthly_inventory` |
| Positive variance without stickers | Spec allows; document in UI warning |
| Transaction size (many voids) | Batch voids; keep one DB transaction; reasonable audit sizes |

## Rollback

- Revert SPA/API to save-only finalize; leave migration columns (harmless defaults).
- Do not auto-unvoid barcodes; restore stock via Monthly inventory if a bad apply lands.
- Feature can be gated by permission check failing closed if needed.

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-09-12

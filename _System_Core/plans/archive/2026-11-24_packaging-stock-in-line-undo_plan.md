---
status: approved
spec: "[[specs/2026-11-24_packaging-stock-in-line-undo]]"
created: 2026-11-24
approved_by: chat
---

# Implementation Plan: Packaging stock-in line undo

> Spec: [[specs/2026-11-24_packaging-stock-in-line-undo]]

## Summary

Add **Undo** on each Packaging report “Scanned by person” size line. Confirm via **AppModal** + **administrator password** (`api_require_admin_override_password`). Backend reverses that day’s sticker + SKU honey stock-in events for `(user, variant, size)`, decrements finished POOL on-hand, restores production gallons, and writes an activity log. Report stays driven by scan history (not POOL).

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]] — daily scan-in reports use `scanned_at` / SKU events, never POOL `current_*` for history; honey stock-in FIFO gallons; Packaging page is gross report only
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]] · `appFeedbackMotion` / `AppModal`
- Reuse `includes/admin_override_password.php` (same as inventory check / leaf pool)

## Impact preview

| Area | Change |
|------|--------|
| API | New POST on `api/v1/bottle_barcodes.php` e.g. `packaging_scan_line_undo` |
| Includes | New undo helper in `honey_bottle_barcodes.php` (+ gallon restore via `honey_inventory.php`) |
| DB | No schema required if we clear/reverse existing rows; optional undo audit via `system_activity_log` |
| SPA | `HoneyPackagingPage.tsx` + small API client; undo modal with password |
| Legacy / Python | none |

_Run `impact.php` on touched helpers before edit._

## Implementation steps

### Phase 1 — Backend undo

1. [ ] Implement `honey_bottle_packaging_scan_line_undo($pdo, $actorUserId, $scanDate, $scannedBy, $variant, $size)`:
   - Load all unique stickers + SKU honey events matching date + scanner + variant + size (same sources as report).
   - Reject if any sticker unit is not packaging `in_stock` (or missing unit while still marked scanned).
   - Reject if POOL `current_*` for size &lt; total qty.
   - Transaction: clear/reverse sticker scan state + remove/void SKU events; `honey_bottle_adjust_aggregate` −qty on variant POOL; restore production gallons for bottle→gallon equivalent (credit most recent / active lot for that variant — document exact helper); best-effort note if bottling packaging materials are not restored.
   - `system_activity_log_write` with line identity + qty (no password).
2. [ ] Wire API: CSRF + auth; **executive** (or agreed role) gate; `api_require_admin_override_password($input)`; call helper; `api_ok` with refreshed summary fields if useful.

### Phase 2 — SPA

1. [ ] Client method for undo POST (`admin_password`, `scan_date`, `scanned_by`, `variant`, `size`).
2. [ ] On each person size line: Undo control → `AppModal` summarizing the line + password input + Confirm/Cancel.
3. [ ] Success: `AppSuccessNotice` / toast + refresh report; errors via `AppErrorBanner`.
4. [ ] Show Undo only when user may undo (executive default per spec).

### Phase 3 — Docs / verify

1. [ ] Regenerate memory bank; problem record + [[002_Memory_Bank]].
2. [ ] Note fragile edge: Packaging undo reverses history + on-hand; not Products Edit.
3. [ ] `tools/verify.ps1` (+ API if XAMPP up); archive plan on ship.
4. [ ] Update [[001_System_State]] Active Spec/Plan when approved.

## Files to touch (expected)

| File | Action |
|------|--------|
| `includes/honey_bottle_barcodes.php` | add undo helper |
| `includes/honey_inventory.php` | gallon restore helper if missing |
| `api/v1/bottle_barcodes.php` | new action |
| `app/src/app/lib/bottleBarcodes.ts` (or equiv) | client |
| `app/src/app/pages/honey/HoneyPackagingPage.tsx` | Undo + modal |
| `_System_Core/003_Fragile_Edges.md` | short note |
| `_System_Core/problems/records/…` | after ship |
| `_System_Core/002_Memory_Bank.md` | link |

## Test plan

- [ ] Stock in a small SKU qty as user A; on Packaging for that date, undo that size line with correct admin password → report qty 0/gone; Products Honey on-hand down; production gallons up.
- [ ] Wrong password → 403, no change.
- [ ] Undo one of two lines for same person → other line unchanged.
- [ ] After POS/pack consumes those bottles → undo fails clearly.
- [ ] Non-executive (if gated) does not see Undo / gets API reject.
- [ ] Reduced motion / modal close Cancel leaves stock unchanged.

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Partial undo leaves history/on-hand split | Single transaction; fail closed on any blocked unit |
| Exact FIFO gallon reverse unknown | Restore equivalent gallons to active variant lot(s); log amount |
| Bottling packaging materials hard to restore | Spec: finished + gallons first; document materials non-restore unless safe helper exists |
| Report “line” spans many events | Undo all events for that key; show qty in modal |

## Rollback

Revert API + SPA files; no migration. Any undos already applied stay (manual re-stock-in if needed).

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-11-24
- Notes: chat — executive-only Undo + admin password; per person/size line

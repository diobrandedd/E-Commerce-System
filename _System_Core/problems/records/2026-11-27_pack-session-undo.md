---
date: 2026-11-27
type: problem-record
status: fixed
area: deliveries-pack-scan
tags: [problem, pack-scan, approvals, undo]
---

# Problem: No way to undo a mistaken pack scan session

## Symptom

Confirmed Pack scan History entries were read-only. Stock stayed deducted (packaging pool, RTS sellable, freebies) with no reverse path for the whole session.

## Cause

→ [[causes/pack-session-history-read-only]]

## Fix

→ [[fixes/pack-session-undo]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_pack_session_undo`) |
| Migration | `sql/migration_phase101_pack_session_undo.sql` |
| Approvals | `includes/override_approvals.php`, `api/v1/bottle_barcodes.php` |
| SPA | `HoneyPackScanPage.tsx` History, `bottleBarcodes.ts`, `ApprovalsPage.tsx` |

## Deploy

- Apply **phase 101** on production before deploy (human).
- `cd app && npm run build` + hard refresh; regenerate memory bank.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- **Plan:** [[plans/archive/2026-11-27_pack-session-undo_plan]]
- **Spec:** [[specs/2026-11-27_pack-session-undo]]

## AI rules

Whole session only. Fail if any unique unit is not still `packed` for that session. Do not reintroduce admin password — use HR approvals.

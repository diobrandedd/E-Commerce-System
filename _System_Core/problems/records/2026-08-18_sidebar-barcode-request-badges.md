# Sidebar barcode request badges

## Problem

Pending barcode/label requests showed a red count only on Honey Production (and CSR Honey labels). Paragis, Black Rice, Cacao, and Honey Comb requests had no sidebar indicator, so staff would not see waiting generate/print work unless they opened each page. Production Manager / HR **Requests** also had no badge even when corrections were pending.

## Cause

Sidebar fetched `bottle_barcodes.php?action=pending_count` only. Dept pouch pending counts existed per product but were never rolled into nav badges. `requestsNav()` was called without `pendingCorrections` except for executive. Notification refresh used `Promise.all`, so one 403 zeroed the whole Requests count.

## Fix

- `dept_pouch_barcodes.php?action=pending_counts` returns pending barcode requests per product the user can operate.
- Sidebar red pills on Inventory → Raw Materials → each product line (Production = bottles, Packaging = Honey Comb).
- Requests nav badge for PM/HR; notification fetch isolates API failures and includes supply unread.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/components/Sidebar.tsx` |
| API | `api/v1/dept_pouch_barcodes.php`, `includes/dept_pouch_barcodes.php` |
| Related | `api/v1/bottle_barcodes.php` (pending_count for request or generate) |
| Related | `api/v1/honey.php` `correction_unread_list` same gate as unread count |
| SPA | `app/src/app/lib/deptPouchBarcodes.ts`, `CorrectionNotificationsContext.tsx` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank

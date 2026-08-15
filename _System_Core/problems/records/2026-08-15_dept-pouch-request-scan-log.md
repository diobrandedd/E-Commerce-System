---
date: 2026-08-15
type: problem-record
status: fixed
area: dept-pouch-barcodes
tags: [problem]
---

# Problem: Paragis / BRC / Cacao / Comb had no scan-in history UI

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Dept pouch label requests only showed aggregate `scanned_count/generated_count`. Operators could not see who scanned each sticker into finished stock or when — unlike honey packaging’s row click → scan log modal.

## Cause

Scan metadata already lived on `dept_pouch_barcodes.scanned_at` / `scanned_by`, but there was no read-only list API or SPA surface for a request’s scanned codes.

## Fix

- PHP: `dept_pouch_request_scan_log()` — paginated SELECT of scanned codes for a request (with scanner name + unit status).
- API: `dept_pouch_barcodes.php?action=request_scan_log`.
- SPA: `DeptPouchScanLogModal`; request table rows in `DeptPouchRequestsPanel` open the modal (Cancel keeps `stopPropagation`). Applies to Paragis, Black Rice, Cacao, and Honey Comb (shared panel).

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/dept_pouch_barcodes.php` |
| API | `api/v1/dept_pouch_barcodes.php` |
| SPA client | `app/src/app/lib/deptPouchBarcodes.ts` |
| SPA | `app/src/app/components/dept/DeptPouchScanLogModal.tsx` |
| SPA | `app/src/app/components/dept/DeptPouchBarcodePanels.tsx` |
| Related | [[generated/modules/dept_pouch_barcodes]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh / regenerate memory bank

## Links

- **Related:** [[problems/records/2026-08-15_packaging-scan-log-modal]]
- **Patterns:** [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]]

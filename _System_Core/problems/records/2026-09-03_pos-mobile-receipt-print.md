---
date: 2026-09-03
type: problem-record
status: fixed
area: pos
tags: [problem, pos, mobile, print]
---

# Problem: POS receipt Print does nothing on phones

## Symptom

Tapping **Print** opened Chrome’s print preview (or a helper tab). Cashiers wanted automatic print with no dialog. POS-58 driver only allows 58×210 / 297 / 3276 — browser print always shows a dialog and can waste paper.

## Cause

`window.print()` / iframe print always shows the browser print UI. Custom paper sizes are not available on this ZPrinter driver.

## Fix

Silent path: SPA builds ESC/POS bytes → `POST pos.php?action=thermal_print` → Windows Winspool RAW (`tools/pos_raw_print.ps1`) to `pos_thermal_printer` (or COM via `pos_thermal_port`). No browser dialog.

## Files

| Role | Path / note |
|------|-------------|
| SPA | `app/src/app/lib/posThermalPrint.ts` |
| API | `api/v1/pos.php` (`thermal_print`) |
| PHP | `includes/pos_thermal_print.php` |
| Tools | `tools/pos_raw_print.ps1` |
| Config | `config.php` → `pos_thermal_printer` |

## Deploy

- Set `'pos_thermal_printer' => 'POS-58(copy of 1)'` (exact Windows name)
- `cd app && npm run build` + hard refresh
- Test Print on POS — paper should feed with no Chrome dialog

## AI rules

Do not use `window.print()` for POS cashier flow when silent print is required. Prefer ESC/POS → Winspool RAW.

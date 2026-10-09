---
date: 2026-10-09
type: problem-record
status: fixed
area: waybill / pdf-to-excel / shopee-spx
tags: [problem, python, openpyxl]
---

# Problem: SPX packing list import — `No module named 'openpyxl'`

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Online Orders → Shopee SPX → **Packing list Excel + PDF** → Import failed with:

`ModuleNotFoundError: No module named 'openpyxl'` from `pdf-to-excel/extractor.py` line 19.

Operator had already `pip install`ed openpyxl, but the error persisted.

## Cause

→ [[causes/windows-py-launcher-vs-python-deps]]

## Fix

→ [[fixes/waybill-python-openpyxl-probe]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/waybill_stamper.php` (`waybill_stamper_resolve_python`) |
| Callers | `includes/pdf_to_excel.php`, stamper, order remarks, SOA, historical import, partner sold |
| Docs | `waybill-stamper/SETUP.md` |

## Deploy

- PHP only (Apache picks up `includes/` on next request). No SPA rebuild.
- Regenerate memory bank after ship.

## Links

- **Fragile edges:** multi-Python PATH on Windows (CSR tools)
- Setup: `waybill-stamper/SETUP.md`

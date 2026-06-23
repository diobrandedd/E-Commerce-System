---
date: 2026-06-21
type: problem-record
status: fixed
area: online-orders-spx
tags: [problem, spx, php, xampp]
---

# Problem: SPX packing list — "Cannot open packing list: xls_….xlsx"

## Symptom

Uploading a valid Shopee packing list (`.xlsx`) plus PDF in Online Orders → SPX name merge showed a generic error: `Cannot open packing list: xls_<hash>.xlsx` (temp upload name). The original filename was fine; PhpSpreadsheet could not open the workbook.

## Cause

PHP **zip extension disabled** in XAMPP (`c:\xampp\php\php.ini`: `;extension=zip`). PhpSpreadsheet requires `ZipArchive` to read `.xlsx` (OOXML zip). Inner exception was swallowed by a catch that only surfaced the basename.

## Fix

1. Enable `extension=zip` in `php.ini` (ensure `php_zip.dll` exists in `ext/`).
2. **Restart Apache** from XAMPP Control Panel (Stop → Start) so mod_php reloads ini.
3. Improved `shopee_spx_packing_list_parse_paths()` to append the underlying exception message and a hint when `ZipArchive` is missing.

## Files

| Role | Path / note |
|------|-------------|
| Environment | `c:\xampp\php\php.ini` |
| Primary | `includes/shopee_spx_packing_list.php` |

## Verify

CLI after enabling zip: `tools/_test_spx_xlsx.php` with user's packing list parses 1 row (tracking `P7202KWUDY9BF`).

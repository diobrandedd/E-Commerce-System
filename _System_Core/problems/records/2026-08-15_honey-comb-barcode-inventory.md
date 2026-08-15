---
date: 2026-08-15
type: problem-record
status: fixed
area: honey-comb-barcodes
tags: [problem, honey-comb, barcodes, inventory]
---

# Problem: Honey Comb finished stock still used Produce instead of jar barcodes

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Honey Comb jars had no sticker request / scan-in path. Finished stock came from the Produce
button (BOM deduct), while Paragis / BRC / Cacao already used dept pouch barcodes. Ops wanted
the same request → generate/print → scan flow, without variant/size, with the UI below Raw Honey
Packaging and its own request list.

## Cause

→ [[causes/honey-comb-left-on-produce]]

`DEPT_POUCH_BARCODE_KEYS` stopped at `pt`, `brc`, `jar_cacao`. Spec explicitly kept Honey Comb on
Produce. Packaging page had bottle batch rows only.

## Fix

→ [[fixes/honey-comb-dept-pouch-barcodes]]

- Added `honey_comb` to `DEPT_POUCH_BARCODE_KEYS` (PHP + SPA); unit noun = jar.
- No schema migration — phase58 `product_key` already covers it.
- Scan still finished +1 only (no raw/packaging/sticker deduct).
- `dept_comb_produce` → 410 Gone.
- New **Honey Comb barcodes** section on `HoneyPackagingPage` (Request / Scan / Generate + own list).
- Raw Honey Comb page keeps bag stock-in / waste; Produce removed; banner + guide point to Packaging.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/dept_pouch_barcodes.php` |
| API | `api/v1/honey.php` — `dept_comb_produce` 410 |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| SPA | `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` |
| SPA | `app/src/app/lib/deptPouchBarcodes.ts`, `deptRawInventory.ts` |
| Spec | [[specs/2026-08-15_honey-comb-barcode-inventory]] |
| Related | [[generated/modules/dept_pouch_barcodes]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh. No SQL.

## Links

- **Prior:** [[records/2026-08-15_dept-pouch-barcodes-cacao]]

## AI rules (optional)

Honey Comb barcode UI lives on Raw Honey → Packaging, not on the raw honey-comb material page.
Do not re-enable Produce without a deduct-on-scan decision.

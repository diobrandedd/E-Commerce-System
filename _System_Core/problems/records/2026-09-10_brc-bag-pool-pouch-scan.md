---
date: 2026-09-10
type: problem-record
status: fixed
area: brc-bag-pool
tags: [problem, inventory, brc]
---

# Problem: Black Rice needed bag-pool pouch scan like Paragis

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

BRC floor still centered on inventorial Produce; ops wanted stock bags → scan pouches with a visible gram pool (default 100 g/pouch) and Settings for future BOM changes.

## Cause

→ [[causes/brc-produce-not-bag-pool]]

## Fix

→ [[fixes/brc-bag-pool-pouch-scan]]

## Files

| Role | Path / note |
|------|-------------|
| Settings | `includes/dept_brc_settings.php`, `api/v1/settings.php` |
| Deduct | `includes/dept_inventory.php`, `dept_pouch_barcodes.php`, `sku_barcodes.php`, `dept_production.php` |
| SPA | `DeptBagPoolStrip.tsx`, `RawDeptMaterialPage.tsx`, `SettingsPage.tsx` |
| Spec | [[specs/2026-09-10_brc-bag-pool-pouch-scan]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
- **Related:** [[problems/records/2026-09-10_paragis-bag-pool-pouch-scan]]

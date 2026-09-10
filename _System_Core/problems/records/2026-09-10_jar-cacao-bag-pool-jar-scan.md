---
date: 2026-09-10
type: problem-record
status: fixed
area: jar-cacao-bag-pool
tags: [problem, inventory, cacao]
---

# Problem: Jar Cacao needed bag-pool jar scan like BRC/Paragis

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Cacao floor still centered on inventorial Produce; ops wanted stock bags → scan jars with a visible gram pool (default 220 g/jar) and Settings for grams per jar.

## Cause

→ [[causes/cacao-produce-not-bag-pool]]

## Fix

→ [[fixes/jar-cacao-bag-pool-jar-scan]]

## Files

| Role | Path / note |
|------|-------------|
| Settings | `includes/dept_jar_cacao_settings.php`, `api/v1/settings.php` |
| Deduct | `dept_inventory.php`, `dept_pouch_barcodes.php`, `sku_barcodes.php`, `dept_production.php` |
| SPA | `DeptBagPoolStrip.tsx`, `RawDeptMaterialPage.tsx`, `SettingsPage.tsx` |
| Spec | [[specs/2026-09-10_jar-cacao-bag-pool-jar-scan]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
- **Related:** [[problems/records/2026-09-10_brc-bag-pool-pouch-scan]]

---
date: 2026-09-10
type: problem-record
status: fixed
area: dept-paragis
tags: [paragis, bag-pool, pouch-scan, settings, bom]
---

# Problem: Paragis WIP steps unused; pouch scan did not burn leaf

> Ops stock bags then scan pouches but UI still taught WIP tea bags and inventory-only scans.

## Symptom

Do next showed Use raw bags / WIP waste; scan copy said barcode stock-in does not deduct raw. Floor wanted bag pool + progress bar and Settings-editable 2 g × 24 BOM.

## Cause

→ [[causes/paragis-wip-inventory-only-scan]]

## Fix

→ [[fixes/paragis-bag-pool-pouch-scan]]

## Files

| Role | Path / note |
|------|-------------|
| Settings | `includes/dept_pt_settings.php` · `api/v1/settings.php` |
| Deduct | `includes/dept_inventory.php` · `includes/dept_pouch_barcodes.php` · `includes/sku_barcodes.php` |
| SPA | `ParagisBagPoolStrip.tsx` · `RawDeptMaterialPage.tsx` · `SettingsPage.tsx` |
| Spec | [[specs/2026-09-10_paragis-bag-pool-pouch-scan]] |

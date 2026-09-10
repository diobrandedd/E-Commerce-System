---
status: shipped
spec: "[[specs/2026-09-10_bag-pool-deduct-toggle]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Bag-pool deduct Settings toggles

> Spec: [[specs/2026-09-10_bag-pool-deduct-toggle]]

## Summary

Add `*.bag_pool_deduct` app_settings (default on) for pt / brc / jar_cacao; gate pouch and SKU scan deduct; Settings switches + bag-pool strip note.

## Impact

| Area | Change |
|------|--------|
| Includes | pt/brc/jar_cacao settings; pouch + sku scan gates; raw detail flag |
| API | BOM get/save include `bag_pool_deduct` |
| SPA | SettingsPage switches; DeptBagPoolStrip note |

## Steps

1. Settings modules + API
2. Gate deduct paths
3. SPA Settings + strip
4. Verify + archive

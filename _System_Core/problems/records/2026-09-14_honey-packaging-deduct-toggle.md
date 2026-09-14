---
date: 2026-09-14
type: problem-record
status: fixed
area: honey
tags: [problem, honey, packaging, settings]
---

# Problem: Bottle scan blocked when empty packaging on-hand is 0

## Symptom

Scanning a honey bottle showed “Not enough Honey bottle w/ cap - 250ml on hand (0 pcs, need 1)” and blocked stock-in even when ops only wanted to credit finished honey.

## Cause

Bottling always called `honey_bottling_deduct_packaging_stocks`, which hard-fails when Packaging Materials for that size (and stickers) are empty.

## Fix

Settings key `honey.packaging_deduct` (default on). When off, `honey_bottling_deduct_packaging_stocks` no-ops. Toggle on System Settings → Honey volume (same save as ml/gallon).

## Files

| Role | Path |
|------|------|
| Setting | `includes/honey_volume.php` |
| Deduct gate | `includes/honey_inventory.php` |
| API | `api/v1/settings.php` |
| SPA | `SettingsPage.tsx` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- No SQL migration (app_settings key created on first save)

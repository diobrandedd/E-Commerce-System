# Fix: Parcel pack TBD tier preview (no 500)

**Date:** 2026-06-05

## Symptom

Selecting TBD tier in preview → `Parcel pack tier not found: honey_250_350_solo`.

## Fix

- `parcel_pack_preview_tier_by_key()` — fetch inactive tier; return warning + ₱0 if placeholder.
- `parcel_pack_tier_is_placeholder()` helper.
- SPA: exclude placeholder tiers from preview dropdown.

**Cause:** [[causes/parcel-tier-inactive-preview-500]]

## Files

- `includes/parcel_pack_costing.php`
- `api/v1/costing.php`
- `app/src/app/components/costing/ParcelPackTiersModal.tsx`

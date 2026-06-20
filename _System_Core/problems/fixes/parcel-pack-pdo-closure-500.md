# Fix: Parcel pack `$pdo` closure (500 on parcel API)

**Date:** 2026-06-05

## Symptom

Costing **Parcel packaging** tab: red "Server error", empty Pack/Landed columns.

## Fix

In `parcel_pack_fetch_tier_lines()`, change closure to:

```php
return array_map(static function (array $row) use ($pdo): array {
```

**Cause:** [[causes/parcel-pack-closure-missing-pdo-use]]

## Files

- `includes/parcel_pack_costing.php`

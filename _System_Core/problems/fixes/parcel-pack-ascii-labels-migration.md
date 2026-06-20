# Fix: Parcel pack ASCII labels + dedupe (??? in UI)

**Date:** 2026-06-05

## Symptom

Tier titles and stock names showed `Honey ??? 1?? 1L`, `Parcel cushion ??? standard`; duplicate materials per tier.

## Fix

1. Run `sql/migration_phase31_parcel_pack_ascii_fix.sql` — ASCII labels, merge cushion SKUs, dedupe `parcel_pack_tier_lines`.
2. Updated `migration_phase30` seeds to ASCII-only for fresh installs.

**Cause:** [[causes/sql-migration-powershell-utf8-corruption]]

## Files

- `sql/migration_phase31_parcel_pack_ascii_fix.sql`
- `sql/migration_phase30_parcel_pack_costing.sql` (ASCII seeds)

## Deploy

```powershell
Get-Content sql/migration_phase31_parcel_pack_ascii_fix.sql -Encoding UTF8 -Raw | c:\xampp\mysql\bin\mysql.exe -u root rts_system
```

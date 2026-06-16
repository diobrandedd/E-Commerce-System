---
type: cause
tags: [cause, sql, windows, utf-8, migrations]
---

# Cause: SQL migration piped through PowerShell corrupts UTF-8 labels to `???`

## What went wrong

`migration_phase30_parcel_pack_costing.sql` used Unicode in seed strings (`—` em dash, `×` multiply, `–` en dash). Running via:

`Get-Content migration.sql -Raw | mysql ...`

without `-Encoding UTF8` stored literal `?` in `parcel_pack_tiers.label` and `stocks.name` (e.g. `Honey ??? 1?? 1L`, `Parcel cushion ??? standard`).

A second run with different corruption (`?` vs `???`) created **duplicate cushion stocks** and **duplicate tier lines** because `NOT EXISTS` checks used the canonical UTF-8 name, not the corrupted row.

## Where in code

- `sql/migration_phase30_parcel_pack_costing.sql` (original seeds)
- Windows deploy path: PowerShell pipe to `mysql.exe`

## Related problems

- [[records/2026-06-05_parcel-pack-ship-bugs]]

## Prevent

- **All migration seed strings: ASCII-only** (`Honey - 1x 1L`, `Parcel cushion - standard`). No em dash / multiply sign in SQL files.
- On Windows run migrations with: `Get-Content path.sql -Encoding UTF8 -Raw | mysql ...` **or** phpMyAdmin / `mysql < file.sql` from cmd.
- Repair script: `sql/migration_phase31_parcel_pack_ascii_fix.sql` (labels + dedupe lines + merge cushion SKUs).

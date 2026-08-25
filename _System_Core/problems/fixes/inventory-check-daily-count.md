# Fix: Check Inventory daily count

`includes/inventory_check.php` + `api/v1/inventory_check.php` + `/inventory/check`.

## Model

| Table | Holds |
|-------|-------|
| `inventory_check_events` | one shared count per date (`open` / `closed`) |
| `inventory_check_scans` | one row per physical unit, snapshotting supplier / batch / bottling date / variant / size and a `flag` (`ok`, `not_in_stock`, `void`) |
| `inventory_check_compare_runs` | one comparison, with scope and mismatch totals |
| `inventory_check_variance_lines` | counted vs system per line, frozen at run time |

## Permissions

| Key | Grants |
|-----|--------|
| `inventory.check.scan` | module access + scanning **any** product line (given to every role preset) |
| `inventory.check.view_all` | summary, per-row scan log, compare, history |
| `inventory.check.manage` | open / close the count, remove anyone's scan |

Without `view_all`, both `summary` and `scan_log` are silently scoped to the caller's own scans — staff see only what they counted.

## Rules that bite

- Scanning **never** mutates stock. Do not add stock side effects here.
- A code can only be counted once per event (`uq_inv_check_scan_code`); a rescan returns `duplicate_event`.
- Compare covers only counted product lines, but within a counted line **every** system batch with stock is included, so an uncounted batch reads as short. That is intentional, not a bug.
- Honey variance is per packaging batch + size (`honey_packaging_rows.current_*`); dept variance is product-level (`dept_inventory_rows.current_qty`) because dept stock has no batch granularity.
- Reopening a closed date reuses the same event row — `count_date` is unique.

Record: [[records/2026-08-25_check-inventory-stock-take]]

---
type: fix
tags: [pattern, packaging, damage, scan]
---

# Fix: Packaging Report damage + damaged pool

## Model

1. **Scan stock out** — `use:` stock entry, on-hand down (unchanged).
2. **Report damage** — `damage:` stock entry (on-hand down) **and** row in `packaging_material_damage_log` (damaged pool).
3. Same permission: `packaging.deduct`.
4. List totals via `packaging_damage_totals?from=&to=` with Today / This week / Custom chips.
5. Remove Deduct for use from detail modal.

## API

| Action | Helper |
|--------|--------|
| `packaging_report_damage` | `material_packaging_report_damage` |
| `packaging_damage_totals` | `material_packaging_damage_totals` |

## SPA

- Header **Report damage** → `PackagingReportDamageModal`
- Row **Damaged** → `PackagingDamageConfirmModal`
- Period chips drive row damaged totals

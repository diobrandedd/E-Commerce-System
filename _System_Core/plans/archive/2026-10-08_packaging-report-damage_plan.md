---
status: shipped
spec: "[[specs/2026-10-08_packaging-report-damage]]"
created: 2026-10-08
approved_by: chat
---

# Implementation Plan: Packaging report damage

> Spec: [[specs/2026-10-08_packaging-report-damage]]

## Summary

Add packaging Report damage (scan + row) that deducts on-hand with `damage:` stock entries and logs to `packaging_material_damage_log` for period totals; remove Deduct for use. Same `packaging.deduct` as Scan stock out.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- [[problems/fixes/packaging-inventory-roles]]

## Impact

| Area | Change |
|------|--------|
| DB | `migration_phase91_packaging_material_damage_log.sql` |
| Includes | `material_inventory.php` — report_damage + damage_totals |
| API | `materials.php` — packaging_report_damage, packaging_damage_totals |
| SPA | PackagingMaterialsPage, report/confirm modals, guide; remove Deduct for use |

## Phases

1. Migration + PHP helpers + API
2. SPA client, modals, page filters/totals, guide
3. Verify + memory bank

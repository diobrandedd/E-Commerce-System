# RTS swapped vs leak disposition

**Status:** implemented 2026-06-19  
**Plan:** `rts_swapped_leak_flows`

## Summary

Split damaged RTS returns into three dispositions on `rts_cases.case_disposition`:

| Disposition | Lines | Sellable sync | Registry |
|-------------|-------|---------------|----------|
| `standard` | required | yes | records |
| `leak` | required | yes | `/rts/leaks` |
| `swapped` | none | **no** | `/rts/swapped` |

## Tracking prefill

`GET rts_cases.php?action=tracking_prefill&tracking=…` loads Online Orders parcel / CSR line + Deliveries date/photo. CSR confirms shop, courier, lines; never auto-saves.

## Migration

Run `sql/migration_phase44_rts_case_disposition.sql` on prod before deploy.

## Registry UI (2026-06-19)

Swapped / Leaks / Records tables: tappable rows, thumbnail columns (before shipment, RTS photo, RTS video), detail modal with full media. Shared component: `app/src/app/components/rts/RtsCaseMediaUi.tsx`. See [[problems/fixes/rts-registry-media-thumbnails]].

## Fragile edges

- Swapped cases must not sync to `rts_inventory_rows` (filter in `rts_inventory_sync_run`).
- `partner` pipeline does not guess RTS shop — CSR picks manually.
- Historical swapped cases may already have synced lines; v1 does not auto-delete inventory rows.

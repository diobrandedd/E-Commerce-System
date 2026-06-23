# RTS registry media UI

**When:** Swapped parcels, Leaks, RTS Records list tables.

## Pattern

Use `@/app/components/rts/RtsCaseMediaUi`:

| Export | Use |
|--------|-----|
| `RTS_MEDIA_TABLE_HEADERS` | Table `<thead>` — Before ship / Photo / Video |
| `RtsCaseMediaTableCells` | Row cells — image thumbs + video thumb (first frame + play icon) |
| `RtsRegistryDetailModal` | Swapped + Leaks row tap → `AppModal` with full gallery |
| `RtsCaseMediaGallery` | Records detail modal (before shipment + all RTS docs) |
| `rtsRegistryRowClassName()` | `cursor-pointer` + `appHoverRow` |

## API

List rows need `before_shipment` + `documentation_attachments` — enriched in `rts_case_handler_list` and `rts_case_handler_list_by_disposition`.

## Do not

- Text-only "Download" / kind links in registry tables
- Return empty swapped/leak lists when `case_disposition` column missing without `damaged_reason` fallback
- Open Record RTS append modal for existing swapped/leak TN

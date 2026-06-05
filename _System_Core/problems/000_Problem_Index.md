# Problem Index

> Chronological hub for **chat-driven fixes** and small optimizations. Each record is a graph node linking **Problem → Cause → Fix → Files**.

## How to use

| You say in Cursor | AI does |
|-------------------|---------|
| "Save to memory bank" | Create `records/YYYY-MM-DD_slug.md` + cause/fix notes + one-line link in [[002_Memory_Bank]] |
| "Implement X, save to memory bank" | Code + problem record + fragile edge if trap |
| Large feature | Still use [[006_AI_Directed_Engineering]] spec → plan → implement |

## Graph shape

```mermaid
flowchart LR
  problem [Problem record]
  cause [Cause note]
  fix [Fix note]
  files [Files / generated modules]
  fragile [003_Fragile_Edges]
  patterns [005_SPA_UX_Patterns]

  problem --> cause
  problem --> fix
  problem --> files
  fix --> fragile
  fix --> patterns
```

## Records (newest first)

- [[records/2026-06-05_edit-requests-submit-server-error]]
- [[records/2026-06-05_deliveries-upload-transaction]]
- [[records/2026-06-05_app-modal-standard]]
- [[records/2026-06-05_shopee-spx-packing-list-import]]
- [[records/2026-06-05_parcel-item-parser-semicolon]]
- [[records/2026-06-05_partner-waybill-flash-no-match]]
- [[records/2026-06-05_online-parcels-csr-forbidden]]
- [[records/2026-06-05_csr-variant-picker-stock]]
- [[records/2026-06-05_online-parcels-packing-photo]]
- [[records/2026-06-05_online-parcels-ui-motion]]
- [[records/2026-06-05_duplicate-tracking-prevention]]
- [[records/2026-06-05_unified-requests-hub]]
- [[records/2026-06-05_deliveries-delete-revert-ship]]
- [[records/2026-06-05_deliveries-save-latency]]
- [[records/2026-06-05_deliveries-sidebar-active-state]]
- [[records/2026-06-05_shopee-spx-review-full-list]]
- [[records/2026-06-05_shopee-spx-tracking-o0]]
- [[records/2026-06-04_csr-variant-resolution-ux]]
- [[records/2026-06-04_honey-packaging-inventory-dashboard]]
- [[records/2026-06-04_csr-waybill-external-storage]]
- [[records/2026-06-04_online-orders-post-ship-qa]]
- [[records/2026-06-04_online-orders-generator]]
- [[records/2026-06-04_inventory-connection]]
- [[records/2026-06-02_pdf-extraction-tracking]]

## Templates

- [[TEMPLATE_Problem_Record]]
- [[TEMPLATE_Cause]]
- [[TEMPLATE_Fix]]

## See also

- [[002_Memory_Bank]] — timeline index (links here)
- [[003_Fragile_Edges]] — promoted traps
- [[006_AI_Directed_Engineering]] — full feature workflow

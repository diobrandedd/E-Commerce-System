---
date: 2026-06-05
type: problem-record
status: fixed
area: csr-online-orders
tags: [problem, online-orders, waybill-stamper]
---

# Problem: Online Orders felt like three separate pipelines

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

CSR **Online Orders** exposed Partner / VIP / Shopee SPX as three opaque pipelines, each embedding prep and stamp in one UX. VIP appended lines during remarks export while stamp did not parse Excel for lines, risking double-save or split responsibility.

## Cause

Legacy `PipelinePicker` + per-pipeline run steps (`PartnerRunStep`, `VipRunStep`, `ShopeeSpxRunStep`) coupled tool-specific prep with `waybill_stamper_process_uploads()` in separate API actions (`run_partner`, `run_vip_stamp`, `run_shopee_*`).

## Fix

Two-phase batch workflow: **prep tools** (download Excel only) + separate **PDF + Excel stamper** (`WaybillStamperTool` → `run_unified_stamp`). Backend: `online_orders_run_unified_stamp()`, `tool_vip_prepare`, `tool_spx_prepare`, `tool_excel_register`, `online_orders_normalize_items_display()` (B1T2 shorthand). Legacy `run_*` actions delegate to unified handler. **Follow-up:** [[problems/records/2026-06-05_online-orders-prep-stamper-split]].

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/online_orders_generator.php` |
| API | `api/v1/online_orders.php` |
| SPA page | `app/src/app/pages/csr/OnlineOrdersGeneratorPage.tsx` |
| SPA components | `ToolsPicker.tsx`, `VipPrepTool.tsx`, `SpxPrepTool.tsx`, `ExcelUploadTool.tsx`, `WaybillStamperTool.tsx` |
| Client | `app/src/app/lib/onlineOrdersApi.ts`, `onlineOrdersTypes.ts` |
| Removed | `PipelinePicker.tsx`, `PartnerRunStep.tsx`, `VipRunStep.tsx`, `ShopeeSpxRunStep.tsx` |
| Theory | `_System_Core/010_Product_Theory.md`, `003_Fragile_Edges.md` |

## Deploy

- `cd app && npm run build` + hard refresh Apache `app/dist/`
- `php tools/memory_bank/generate_all.php`

## Links

- **Fragile edges:** [[003_Fragile_Edges#Online Orders duplicate tracking]]
- **Patterns:** [[005_SPA_UX_Patterns]]
- **Plan:** [[plans/archive/2026-06-05_waybill_stamper_and_online_orders_remake_plan]]
- **Spec:** [[specs/waybill stamper and online orders remake]]

## AI rules (optional)

Prep tools must not call `online_orders_lines_append()` — only `run_unified_stamp` appends after stamp + parse.

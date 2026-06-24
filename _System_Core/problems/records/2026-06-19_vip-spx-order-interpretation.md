# VIP / SPX order interpretation + CSR item builder

## Problem

Real J&T/SPX courier exports use different columns and messy item text. VIP prep dropped trailing 350ml bottles, lost dept products in honey combos, used phone as tracking, and 2L/3L stock cards showed only one min number.

## Cause

- `order_remarks_workbook.py` required a Remarks column; SPX export uses Item in Parcel only.
- No tracking column extraction; VipPrepTool used phone as tracking.
- `orderRemarksParse.ts` single-product model; `honey_parcel_item_parser.php` skipped dept when honey in same segment and did not expand 350ml LAD / B2T1 counts.

## Fix

- Extended J&T/SPX profiles + workbook detect (tracking, itemText, fuzzy headers).
- **2026-06-19 follow-up:** fixed SPX `Sheet1` skip (wrong 3-tuple unpack + generic-only probe) and openpyxl warning polluting JSON stdout.
- Added `itemLineBuilder.ts` + `ItemLineBuilder.tsx` (product/variant/qty rows) on VIP + SPX carousels with full remarks visible.
- Per-color stock breakdown on variant preset cards for 2L/3L/350ml LAD.
- Parcel parser: combo split, 350ml multi-letter expansion, promo bottle counts.

## Files

- `includes/order_remarks.php`, `includes/order_remarks_workbook.py`
- `includes/honey_parcel_item_parser.php`
- `app/src/app/lib/itemLineBuilder.ts`, `orderRemarksParse.ts`, `honeyPackagingStock.ts`, `orderRemarksApi.ts`
- `app/src/app/components/csr/onlineOrders/ItemLineBuilder.tsx`, `AmbiguousOrderCarousel.tsx`, `ItemResolverCarousel.tsx`
- `app/src/app/components/csr/orderRemarksMotion.tsx`, `VariantCardStrip.tsx`
- `app/src/app/components/csr/onlineOrders/VipPrepTool.tsx`
- `_System_Core/specs/2026-06-19_vip-spx-order-interpretation.md`

## Follow-up (2026-06-19 PM)

→ [[records/2026-06-19_vip-spx-parser-pitfalls]] — full session hub (combo drop, LLA, 250ml, J&T 350ml, collapsible UI).

→ [[records/2026-06-19_waybill-stamp-text-only]] — PDF stamp text-only (no border).

**Fix guides:** [[fixes/vip-spx-order-remarks-parser-guards]] · [[fixes/waybill-stamp-text-only-no-border]]

## Follow-up (2026-06-19): Paragis VIP qty auto-submitted as 1

`1 x 2 Paragis Tea` used honey-style `extractQty` → qty 1; dept-only rows skipped ambiguous carousel. Fixed: `extractDeptQty` / `honey_parcel_extract_dept_qty`; `needsDeptConfirm` gates export until CSR saves builder line.

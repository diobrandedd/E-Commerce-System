# VIP arranger + merger two-tool workflow

## Problem

VIP prep expected a post-waybill courier export. New workflow uploads a **VIP order list** first (remarks only), then a separate **tracking export** when couriers return waybills.

## Cause

Single-tool flow used phone/waybill fallback; no join on Parcel Name ↔ Order Number / Customer Reference.

## Fix

- Split into **VIP Excel arranger** (order list + carousel → session stash) and **VIP Excel merger** (tracking map → iPYX).
- `vip_normalize_parcel_ref()` strips `PKE` prefix for J&T.
- Header detection: removed `ORDER LIST` marker (matched row 3 before real header row 8).

## Files

- `includes/order_remarks.php`, `includes/order_remarks_workbook.py`
- `includes/online_orders_generator.php`, `api/v1/online_orders.php`
- `app/src/app/components/csr/onlineOrders/VipPrepTool.tsx`, `VipMergeTool.tsx`, `ToolsPicker.tsx`
- `app/src/app/lib/itemLineBuilder.ts`, `orderRemarksParse.ts`, `onlineOrdersApi.ts`

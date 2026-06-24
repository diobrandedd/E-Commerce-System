---
date: 2026-06-19
type: problem-record
status: fixed
area: csr-online-orders
tags: [problem, spa, waybill]
---

# Problem: Partner seller + TikTok pipeline + parcels registry gaps

## Symptom

Partner stamps had no Ian/Jono/Joven tracking; no TikTok pipeline; boss could not filter/export by order no., courier, or partner seller on Online Parcels.

## Cause

Single `partner` pipeline with no `partner_seller` column; ENUM lacked `tiktok`; parcels list UI hid `order_no` and had minimal filters.

## Fix

- `migration_phase48`: `partner_seller`, `tiktok` enum, `courier` VARCHAR(191)
- `WaybillStamperTool`: seller picker + TikTok; API stores seller on stamp
- `OnlineParcelsPage`: order no column, date/partner/pipeline/courier filters, sort, page size, row export

## Files

- `sql/migration_phase48_online_orders_partner_seller_tiktok.sql`, `tools/apply_migration_phase48.php`
- `includes/online_orders_generator.php`, `includes/online_order_parcels.php`
- `api/v1/online_orders.php`, `api/v1/parcels.php`
- `WaybillStamperTool.tsx`, `OnlineParcelsPage.tsx`, `parcelsApi.ts`

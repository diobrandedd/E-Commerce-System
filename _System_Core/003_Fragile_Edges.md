# Fragile Edges (Manual)

> Curated traps that regex scanners **cannot** reliably detect. Read this before editing shared infrastructure.

See also: [[000_System_Architecture]] · [[generated/110_Include_Graph]] · [[generated/120_Database_Tables]]

## Dual auth systems

| System | Session helper | User table | Used by |
|--------|----------------|------------|---------|
| **IMS (SPA)** | `ims_current_user()` in `includes/auth_check.php` | `users` | `api/v1/*`, React SPA |
| **Legacy RTS** | `rts_auth_user()` in `includes/auth.php` | `app_users` | `record.php`, `api/case.php`, `api/delivery_*.php`, legacy pages |

Do not assume a session from one system works on endpoints built for the other.

## `db.php` side effect

Every include of `includes/db.php` also loads `includes/forwarding.php` and may call `rts_forwarding_maybe_redirect()`. CLI scripts and tests can behave differently on localhost vs public/ngrok URLs.

## Reverse include: includes → api/v1

`includes/honey_pricing.php` requires `api/v1/_costing_helpers.php`, which boots `_bootstrap.php` (JSON headers, session). **Do not add new includes → api/v1 requires.** See [[generated/110_Include_Graph#Risky edges (includes → api/v1)]].

## Honey module circular dependency

`honey_inventory.php` ↔ `honey_pricing.php` is broken with lazy load via `honey_inventory_pricing()`. Do not add top-level cross-requires between these files.

## Runtime schema probes

Code adapts SQL when columns are missing. Changing schema without updating probes causes silent behavior changes:

- `rts_db_has_column()` — `includes/rts_schema.php`
- `rts_cases_void_sql()` — void column handling
- `honey_warehouse_has_pricing_columns()` / `honey_warehouse_unit_cost_column()` — pricing columns
- `honey_pos_column_exists()` — POS schema variants

After any migration, grep for these helpers and update callers.

## Shared access via waybill

`pdf_to_excel.php` and `order_remarks.php` reuse `waybill_stamper_user_can_access()` / `waybill_stamper_require_access()` from `includes/waybill_stamper.php`. `online_orders_generator.php` delegates to the same helper (includes **`csr_staff`**). Changing waybill access rules affects PDF→Excel, order remarks, and Online Orders unintentionally.

## PhpSpreadsheet 2.x (`online_orders_generator.php`)

Project uses `phpoffice/phpspreadsheet` **^2.0**. Do **not** use removed APIs:

- `getCellByColumnAndRow` / `setCellValueByColumnAndRow` / `getStyleByColumnAndRow`
- `(int) $sheet->getHighestColumn()` — column letters must stay strings; use `Coordinate::columnIndexFromString()`

Use `online_orders_coord($col, $row)` → `$sheet->getCell(...)` / `setCellValue(...)`. Match pattern in `includes/sp_sort_service.php` (`getCell('B4')`).

## Shopee / marketplace PDF item lines

Labels often show size only (e.g. `1 x 1L HONEY w/SF`) with **no Light/Amber/Dark**. Extractor marks these incomplete via `items_variant` in `missing_fields`. SPA must resolve in Shopee SPX carousel (`honeyItemNeedsVariantResolution` in `app/src/app/lib/honeyItemVariant.ts`) before `run_shopee_resolve` — do not treat non-empty `items` as packer-ready.

## Online order parcels + ship deduct

- **`online_order_parcels`** — created on `batch_finalize` from `csr_online_order_lines`; UNIQUE `tracking_number`; parser must pass before insert.
- **Ship** — `api/v1/deliveries.php` `ship_parcel` requires `delivery_item_id` (photo uploaded first); uses `honey_pos_deduct_packaging_aggregate()` via `honey_parcel_apply_deduct()`; idempotent if already `shipped`.
- **Parser** — `includes/honey_parcel_item_parser.php` must stay aligned with `orderRemarksParse.ts` / Shopee variant rules.
- **Deliveries auth** — SPA uses IMS (`executive`, `prod_manager` only); legacy `api/delivery_bulk.php` still uses `app_users` submitter id via `deliveries_upload.php`.

## Honey “total inventory” scope

`packaging_total_inventory` counts **`honey_packaging_rows.current_*`** where `batch_no` is non-empty. Do not sum warehouse or production for this dashboard — those stages use gallons, not per-bottle SKUs. Sales charts use `honey_sales_log`; inventory charts use packaging current columns.

## Online Orders stamp UX (SPA)

After any pipeline stamp, **`OnlineOrdersGeneratorPage` holds `stampResult`** and renders `StampResultPanel` until the user explicitly continues. Do not call `setStep("post_run")` on stamp API success — only on “Done — master Excel” or “Process another pipeline”.

## Edit-request table whitelist

`api/v1/edit_requests.php` defines `ER_ALLOWED_TABLES`. New editable tables must be added there or executive edit approvals will fail.

## POS discount → honey POS

`includes/pos_discount_requests.php` depends on `includes/honey_pos.php` for catalog and receipt payload. POS discount schema or checkout changes must stay aligned.

## RTS mirror → honey deduct

`rts_inventory_deduct_sell()` in `includes/rts_inventory_sync.php` bridges RTS pool rows into honey packaging deduct when `category=honey`. Changing category values or honey deduct signatures breaks SPA sell flow.

## CSR waybill file storage

When `config.php` sets `storage.root` (e.g. `C:/xampp/htdocs/rts_storage`), stashes and outputs for waybill stamper, PDF→Excel, order remarks, and online orders live under:

- `waybill_stamper/{uploads,outputs,stash}`
- `pdf_to_excel/{stash,outputs,work}`
- `order_remarks/{stash,outputs}`
- `online_orders/{outputs,work}`

Resolved via `rts_storage_csr_tool_root()` in `includes/rts_storage.php`. If `storage.root` is empty, legacy paths remain under `rts/storage/`. Downloads still use PHP session tokens (not public URLs).

## Before you edit shared code

1. Run `php tools/memory_bank/impact.php <function_name>` (Windows: `c:\xampp\php\php.exe tools/memory_bank/impact.php <function_name>`)
2. Read the matching module note under [[generated/130_Module_Index]]
3. After merge, regenerate: `php tools/memory_bank/generate_all.php` and log impact in [[002_Memory_Bank]]

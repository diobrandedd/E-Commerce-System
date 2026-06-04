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

`pdf_to_excel.php` and `order_remarks.php` reuse `waybill_stamper_require_access()` from `includes/waybill_stamper.php`. Changing waybill access rules affects PDF→Excel and order remarks unintentionally.

## Edit-request table whitelist

`api/v1/edit_requests.php` defines `ER_ALLOWED_TABLES`. New editable tables must be added there or executive edit approvals will fail.

## POS discount → honey POS

`includes/pos_discount_requests.php` depends on `includes/honey_pos.php` for catalog and receipt payload. POS discount schema or checkout changes must stay aligned.

## RTS mirror → honey deduct

`rts_inventory_deduct_sell()` in `includes/rts_inventory_sync.php` bridges RTS pool rows into honey packaging deduct when `category=honey`. Changing category values or honey deduct signatures breaks SPA sell flow.

## Before you edit shared code

1. Run `php tools/memory_bank/impact.php <function_name>` (Windows: `c:\xampp\php\php.exe tools/memory_bank/impact.php <function_name>`)
2. Read the matching module note under [[generated/130_Module_Index]]
3. After merge, regenerate: `php tools/memory_bank/generate_all.php` and log impact in [[002_Memory_Bank]]

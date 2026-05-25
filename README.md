
# RTS system

## Upload storage (outside the project folder)

To update the PHP code (git pull / branch switch) without losing or conflicting with uploaded photos and spreadsheets, point storage to a folder **outside** this repository.

1. Copy `config/config.sample.php` to `config/config.php` if needed.
2. Set in `config/config.php`:

```php
'storage' => [
    'root' => 'C:/xampp/htdocs/rts_storage',  // outside the rts/ git folder
    'web_base' => '/rts_storage',             // URL path under htdocs
],
```

3. Create on disk (Apache will serve files from here):

- `rts_storage/rts_documentation/` — RTS parcel photos/videos  
- `rts_storage/deliveries_documentation/` — pre-dispatch delivery photos  
- `rts_storage/sp_sort/` — SP Sort Excel exports  

4. **Move existing files** (one-time), if you already uploaded into the project:

- From `rts/assets/rts_documentation/*` → `rts_storage/rts_documentation/`
- From `rts/assets/deliveries_documentation/*` → `rts_storage/deliveries_documentation/`
- From `rts/assets/sp_sort/*` → `rts_storage/sp_sort/`

Filenames in the database stay the same; only the folder on disk changes.

Leave `storage.root` empty to keep using `assets/` inside the project (not recommended for git workflows).

## Inventory Phase 1.5 bootstrap

After running `sql/migration_phase1_inventory_auth.sql`, run one-time setup page:

- `auth/phase1_5_setup.php`

If you already ran an older version of the Phase 1 migration before username/ID login was introduced, run this patch first:

- `sql/migration_phase1_inventory_auth_username_id.sql`

It will:

- create/update first inventory executive user (`users` table)
- create one POS PIN (`pos_pins` table)
- hash both password and PIN using `password_hash(..., PASSWORD_BCRYPT)`
- create lock file `config/phase1_5_bootstrap.lock` so setup cannot be rerun accidentally

If you need to rerun intentionally, delete that lock file first.

Inventory login format:

- username is used for login
- default password is employee ID number

User password change pages:

- RTS accounts: `settings.php`
- Inventory phase accounts: `auth/settings.php`

## Inventory migrations (phases 2-8)

Run these SQL files in order after phase 1:

- `sql/migration_phase2_inventory_stocks.sql`
- `sql/migration_phase3_inventory_products.sql`
- `sql/migration_phase4_inventory_costing.sql`
- `sql/migration_phase5_pos_ar_reports.sql`
- `sql/migration_phase6_honey_inventory_v3.sql` — Honey warehouse/production/packaging, sales log, correction requests, dept rows, `hr` role
- `sql/migration_phase7_rts_inventory_sync.sql` — `rts_inventory_rows`, `rts_sync_state` (run after phase 6)
- `sql/migration_phase8_suppliers_master.sql` — `suppliers` master table, `honey_warehouse_rows.supplier_id`, ID photos in `supplier_valid_ids/` storage bucket
- `sql/migration_phase9_packaging_one_per_production.sql` — one packaging row per production row (partial bottling adds to same batch)

Phase 4 adds:

- `stock_price_history` (audit trail of stock price changes)
- `product_cost_history` (costing recalculation history triggered by stock price updates)

Phase 5 adds:

- `pos_sales` and `pos_receipt_items` (walk-in POS terminal sales)
- `ar_credits` (staff "take a product on credit" accounts receivable)

These back the React SPA pages `POSPage`, `ARCreditsPage`, and `ReportsPage`.

## Honey inventory v3 vs generic costing

Two parallel models share one database (`rts_system`):

| Area | Tables / API | SPA |
|------|----------------|-----|
| **Honey ops (v3)** | `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`, `honey_sales_log`, `inventory_correction_requests` | `/inventory/honey/*`, `/inventory/requests` via `api/v1/honey.php` |
| **Generic costing** | `stocks`, `stock_entries`, `products`, `product_formula` | `/costing` via `api/v1/costing.php` (unchanged) |
| **RTS inventory mirror** | `rts_inventory_rows` synced from `rts_line_items` | `/rts` via `api/v1/rts.php` + `rts_sync.php` |

Operational RTS recording stays in legacy `record.php` / `rts_cases`. Sync creates **new inventory rows per return event**; PM deduct/sell only updates inventory rows and `honey_sales_log` (`source=rts`), never deletes source line items.

**Roles:** `prod_manager` mutates Honey + RTS inventory; `executive` approves corrections and can direct qty adjust; `hr` is read-only across Honey, requests history, and RTS inventory.

## Supplier master (phase 8)

- **Executive** — `/suppliers` (Supplier Master): register suppliers (name, contact, payment account/number, address, valid ID photo, category: Honey / Paragis / Black Rice / Cacao). API: `api/v1/suppliers.php`.
- **Production Manager** — Honey warehouse stock-in: **combobox only** (type to filter names); cannot add suppliers. Posts `supplier_id` + variants; supplier code = initials of full display name + arrival date (`MMDDYY`), same as `honey_supplier_code()`.
- **Production → Packaging** — PM enters bottle size lines (250ml, 350ml, 1L, gallon); system auto-deducts gallon/s from volume (1 gal ≈ 3,785 ml exact in code). Partial runs update the **same** packaging batch row; first run sets batch + bottling date.
- ID photos stored under configured storage root: `supplier_valid_ids/` (or `assets/supplier_valid_ids/` if storage root is empty).

## React SPA (Pyx Tracker)

The new SPA lives in `app/` and is served at `/rts/app/dist/` once built:

```
cd app
npm install
npm run build        # outputs to app/dist/
```

For development, `npm run dev` runs Vite on `http://localhost:5173` and proxies
`/rts/api/*` to the local XAMPP Apache. The PHP JSON gateway lives in
`api/v1/*.php` and reuses the existing IMS session/CSRF helpers.

## HTTPS forwarding (ngrok) for mobile camera

Edit **`config/forwarding.php`** when you switch ngrok accounts (testing vs live):

```php
'enabled' => true,
'public_url' => 'https://your-subdomain.ngrok-free.dev',
'label' => 'testing',  // or 'live'
```

Copy from `config/forwarding.sample.php` if the file does not exist yet.

When enabled:

- Opening the site on a **phone/LAN IP** (e.g. `http://192.168.1.50/rts/...`) redirects to the HTTPS ngrok URL (required for camera/QR).
- **`localhost` on the server PC** is not redirected by default (local dev).
- Legacy pages (`record.php`, `deliveries/`) and the React app use the same config.
- API routes under `/rts/api/` are not redirected (avoids breaking JSON `fetch`).

Start ngrok pointing at port 80, then set `public_url` to the HTTPS URL ngrok shows (must match exactly, e.g. `*.ngrok-free.dev` vs `*.ngrokfree.dev`).

**Refresh on deep links:** The SPA ships `app/public/.htaccess` (copied to `app/dist/` on build) so URLs like `/rts/app/dist/inventory/honey/warehouse` reload correctly instead of Apache 404.

**ngrok + API:** All `fetch` calls to `/rts/api/` automatically send `ngrok-skip-browser-warning` when the page hostname contains `ngrok`, so refresh does not hit ngrok’s HTML interstitial instead of JSON.

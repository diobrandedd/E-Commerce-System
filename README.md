
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

## Inventory migrations (phases 2-4)

Run these SQL files in order after phase 1:

- `sql/migration_phase2_inventory_stocks.sql`
- `sql/migration_phase3_inventory_products.sql`
- `sql/migration_phase4_inventory_costing.sql`

Phase 4 adds:

- `stock_price_history` (audit trail of stock price changes)
- `product_cost_history` (costing recalculation history triggered by stock price updates)

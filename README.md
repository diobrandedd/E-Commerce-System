# Pyx Tracker

**Finest Wellness PH** — unified operations platform for inventory, production, returns (RTS), sales, and fulfillment tools. The repo folder is still `rts/` for historical reasons (Apache path `/rts/`), but the product is **Pyx Tracker**, not “RTS only.”

| Layer | Stack |
|--------|--------|
| **SPA** | React + Vite + TypeScript (`app/`) |
| **API** | PHP JSON gateway (`api/v1/*.php`) |
| **Legacy UI** | PHP pages (`record.php`, `deliveries/`, etc.) |
| **Database** | MySQL (`rts_system`) |
| **Heavy jobs** | Python (`waybill-stamper/processor.py`) |

**Production URL (local):** `http://localhost/rts/app/dist/`  
**API base:** `/rts/api/v1/`

---

## What this system covers

```text
Pyx Tracker
├── Inventory & production (Honey v3, dept stubs, costing)
├── RTS (returns tracking + inventory mirror)
├── Sales & POS (walk-in terminal, reports)
├── Suppliers & AR
├── CSR tools (waybill stamping)
└── Legacy / bridge pages (deliveries upload, old RTS record flow)
```

---

## Current progress (live today)

### React SPA (primary UI)

Built app: `cd app && npm install && npm run build:prod` → `app/dist/`

| Area | Routes | Roles | Notes |
|------|--------|-------|--------|
| **Dashboard** | `/dashboard` | Most roles | Role-specific landing |
| **Honey inventory** | `/inventory/honey/*` | Executive, PM, HR | Warehouse → Production → Packaging |
| **Dept inventory** | `/inventory/paragis`, `/inventory/cacao-brc` | Executive, PM, HR | UI present; depth varies vs Honey |
| **Correction requests** | `/inventory/requests` | Executive, PM, HR | Approvals + notifications |
| **Product costing** | `/costing` | Executive, PM | Stocks, formulas, honey variant groups |
| **Supplier master** | `/suppliers` | Executive | ID photos, categories |
| **RTS inventory** | `/rts` | Executive, PM, HR, CSR | Mirror of returns; sync from legacy cases |
| **Reports / Sales** | `/reports`, `/sales` | Executive, PM | POS + AR-backed reporting |
| **AR credits** | `/ar` | Several roles | Staff credit tracking |
| **Waybill stamper** | `/waybill-stamper` | Executive, CSR TL | PDF waybills + iPYX Excel → stamped PDF |
| **Staff / settings** | `/staff`, `/settings` | Executive | User management |
| **POS terminal** | `/pos`, `/pos/honey` | POS PIN | Walk-in sales |

Auth: `api/v1/auth.php` (session + CSRF). SPA proxies API in dev via Vite.

### Legacy PHP (still in use)

| Page | Purpose |
|------|---------|
| `record.php` / `records.php` | Original RTS case recording |
| `deliveries/` | Pre-dispatch delivery photo upload |
| `login.php`, `portal.php` | Older auth / restricted portal |
| `executive.php`, `dashboard.php` | Legacy dashboards (being superseded by SPA) |

RTS **operational** data still originates here; the SPA **inventory mirror** is updated via `api/v1/rts_sync.php`.

### Waybill stamper

- **SPA:** `/waybill-stamper`
- **API:** `api/v1/waybill.php` (`stash` → per-file upload, `process`, `download`)
- **Engine:** `waybill-stamper/processor.py` (Python, Tesseract, Poppler)
- **Setup:** [`waybill-stamper/SETUP.md`](waybill-stamper/SETUP.md)

### Infrastructure

- **Upload storage** can live outside the git tree (`config/config.php` → `storage.root`)
- **ngrok / HTTPS forwarding** for mobile camera & remote access: `config/forwarding.php`
- **SQL migrations:** `sql/migration_phase*.sql` (run in order)

---

## Roles (SPA)

| Role | Typical access |
|------|----------------|
| `executive` | Full inventory, costing, suppliers, RTS, waybill, staff, settings |
| `prod_manager` | Honey ops, RTS inventory, reports, sales, AR (own) |
| `hr` | Read-only Honey + RTS + requests history |
| `csr_tl` | Waybill stamper, RTS inventory |
| `csr_staff` | RTS record flow |
| `team_leader` / `staff` | Dept stock input, daily count, edit requests (legacy-style dept tools) |
| `pos` | POS terminal only |

Department (CSR, Honey, etc.) is resolved from the `users` / `departments` tables and mapped in `api_normalize_role()`.

---

## Project layout

```text
rts/
├── app/                    # React SPA (Pyx Tracker UI)
├── api/v1/                 # JSON API endpoints
├── includes/               # PHP shared logic (auth, honey, waybill, …)
├── config/                 # config.php, forwarding.php
├── sql/                    # Database migrations
├── waybill-stamper/        # Python processor + SETUP.md
├── storage/                # App-private files (waybill temp, etc.)
├── assets/                 # Legacy static uploads (prefer external storage)
├── record.php, deliveries/ # Legacy RTS / logistics pages
└── README.md               # This file
```

---

## Quick start (development)

### 1. Database

1. Create MySQL database `rts_system` (or name in `config/config.php`).
2. Run migrations in order: `sql/migration_phase1_*.sql` through `migration_phase9_*.sql`.
3. One-time bootstrap (if applicable): `auth/phase1_5_setup.php`  
   - Default password for new users is often **employee ID** (see migration docs).

### 2. PHP / Apache (XAMPP)

- Point Apache document root at `htdocs`; app is served under `/rts/`.
- Enable `shell_exec` if using Waybill Stamper (see SETUP.md).
- Optional: raise `upload_max_filesize` / `post_max_size` for large PDF batches.

### 3. React SPA

```bash
cd app
npm install
npm run dev          # http://localhost:5173 — proxies /rts/api to XAMPP
npm run build:prod   # output → app/dist/ for Apache
```

### 4. Config

```bash
cp config/config.sample.php config/config.php    # if missing
cp config/forwarding.sample.php config/forwarding.php   # optional ngrok
```

---

## Upload storage (recommended for git workflows)

Store uploads **outside** this repo so `git pull` never touches user files.

```php
// config/config.php
'storage' => [
    'root' => 'C:/xampp/htdocs/rts_storage',
    'web_base' => '/rts_storage',
],
```

Create under that root:

- `rts_documentation/` — RTS parcel photos/videos  
- `deliveries_documentation/` — pre-dispatch delivery photos  
- `sp_sort/` — SP Sort Excel exports  
- `supplier_valid_ids/` — supplier ID photos (if using supplier master)

Leave `storage.root` empty to keep using `assets/` inside the project (not ideal for git).

---

## HTTPS forwarding (ngrok)

Edit **`config/forwarding.php`** when switching tunnels:

```php
'enabled' => true,
'public_url' => 'https://your-subdomain.ngrok-free.dev',
'label' => 'testing',
```

- Phone/LAN HTTP URLs can redirect to HTTPS ngrok (camera/QR).
- `localhost` on the dev PC is usually not redirected.
- **`/rts/api/` is never redirected** (JSON `fetch` must stay on-origin).
- SPA build includes `.htaccess` for deep-link refresh under `/rts/app/dist/`.

---

## Data model (high level)

| Domain | Main tables / API |
|--------|-------------------|
| **Honey v3** | `honey_*` tables, `api/v1/honey.php` |
| **Generic costing** | `stocks`, `products`, `product_formula`, `api/v1/costing.php` |
| **RTS mirror** | `rts_inventory_rows`, `api/v1/rts.php`, `rts_sync.php` |
| **Suppliers** | `suppliers`, `api/v1/suppliers.php` |
| **POS / AR** | `pos_sales`, `ar_credits`, `api/v1/pos.php`, `ar.php` |
| **Waybill** | Session stash + `storage/waybill-stamper/`, `api/v1/waybill.php` |

Honey ops and generic costing share one DB but serve different screens.

---

## API index (`api/v1/`)

| File | Purpose |
|------|---------|
| `auth.php` | Login, session, CSRF |
| `honey.php` | Warehouse / production / packaging |
| `costing.php` | Stocks & product costing |
| `rts.php` / `rts_sync.php` | RTS inventory mirror |
| `suppliers.php` | Supplier master |
| `staff.php`, `settings.php` | Admin |
| `reports.php`, `sales.php` | Reporting |
| `pos.php`, `ar.php` | POS & credits |
| `waybill.php` | Waybill stamper |
| `dashboard.php`, `edit_requests.php` | Dashboard & corrections |

---

## Planned / future additions

| Item | Status | Notes |
|------|--------|--------|
| **CSR portal expansion** | Planned | `portal.php` is a stub; more CSR self-service pages |
| **Paragis / Cacao-BRC inventory** | Partial | Routes exist; operational parity with Honey v3 |
| **Waybill stamper** | ✅ Shipped | Optional: batch history, progress streaming, CSR staff access |
| **Legacy → SPA migration** | Ongoing | `record.php` RTS flow still primary for case capture |
| **Team leader / staff SPA** | Partial | Stock input & daily count in SPA; some flows still legacy |
| **Executive legacy pages** | Deprecated path | Prefer SPA dashboard / reports |
| **Dashboard shortcuts** | Planned | Quick links per role (e.g. waybill, RTS sync) |
| **Stronger production deploy** | Planned | Env-based config, CI build, documented PHP/Python paths |
| **Automated tests** | Planned | API smoke tests, waybill processor fixture runs |

---

## Related docs

- [`waybill-stamper/SETUP.md`](waybill-stamper/SETUP.md) — Python, Tesseract, Poppler, PHP limits  
- [`app/README.md`](app/README.md) — SPA dev commands  
- [`config/forwarding.sample.php`](config/forwarding.sample.php) — ngrok fields  

---

## License / internal use

Internal Finest Wellness PH tooling. Do not commit secrets (`config/config.php`, cookies, `.env`) to git.

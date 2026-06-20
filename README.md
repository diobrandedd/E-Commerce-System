# Pyx Tracker

**Finest Wellness PH** — unified operations platform for inventory, production, returns (RTS), sales, and fulfillment tools. The repo folder is still `rts/` for historical reasons (Apache path `/rts/`), but the product is **Pyx Tracker**, not “RTS only.”

| Layer | Stack |
|--------|--------|
| **SPA** | React + Vite + TypeScript (`app/`) |
| **API** | PHP JSON gateway (`api/v1/*.php`) |
| **Legacy UI** | PHP pages (`record.php`, `deliveries/`, etc.) |
| **Database** | MySQL (`rts_system`) |
| **Heavy jobs** | Python (`waybill-stamper/`, `pdf-to-excel/`) |

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
├── CSR tools (Online Orders batch wizard)
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
| **Online Orders** | `/waybill/online-orders` | Executive, CSR TL, CSR staff | Batch wizard: 4 tools (3 prep + PDF+Excel stamper) → one master Excel |
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

### Online Orders (CSR)

| Tool | SPA route | API |
|------|-----------|-----|
| **Online Orders Generator** | `/waybill/online-orders` | `api/v1/online_orders.php` |

One **active batch** per session. The **tools page** has **four independent cards** — there is no prep → stamper chain:

| Tool | Job |
|------|-----|
| VIP Excel arranger | Courier Excel → resolve remarks → **download** iPYX sheet → **Back to tools** |
| SPX name merge | Packing list / PDF → review → **download** iPYX sheet → **Back to tools** |
| Validate existing Excel | Check partner workbook → **download** → **Back to tools** |
| **PDF + Excel stamper** | User returns to tools and picks this separately: prepared Excel + waybill PDFs → `run_unified_stamp` → batch lines |

**Done** merges all stamped parcel lines into one master iPYX-style Excel (cols B–H) and persists rows in `csr_online_order_lines` for parcels/inventory.

Underlying engines (unchanged): `waybill-stamper/processor.py`, `pdf-to-excel/extractor.py`, `includes/order_remarks_workbook.py` — invoked via `waybill.php`, `pdf_to_excel.php`, and `order_remarks.php` from the orchestration layer in `includes/online_orders_generator.php`.

**DB migration (once):** `Get-Content sql/migration_phase17_csr_online_orders.sql | mysql -u root rts_system` (or equivalent on your DB name)

Legacy SPA routes `/waybill/stamper`, `/waybill/pdf-to-excel`, and `/waybill/order-remarks` redirect to `/waybill/online-orders`. `/waybill-stamper` redirects there as well.

Full install steps for Python / OCR: [Quick start, section 3](#3-waybill--pdf-tools-setup-windows) and [`waybill-stamper/SETUP.md`](waybill-stamper/SETUP.md).

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
| `csr_tl` | Online Orders, RTS inventory |
| `csr_staff` | Online Orders, RTS record flow |
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
├── waybill-stamper/        # Stamper Python + SETUP.md
├── pdf-to-excel/           # PDF→Excel extractor.py
├── storage/                # App-private files (waybill, order-remarks, pdf-to-excel)
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
- **Waybill / PDF → Excel / Order remarks** need `shell_exec` enabled in `php.ini` (see [section 3 below](#3-waybill--pdf-tools-setup-windows)).
- Raise upload limits for large waybill PDFs (see same section).

### 3. Waybill & PDF tools setup (Windows)

Required for **Waybill Stamper**, **PDF → Excel**, and OCR fallback on image-only label pages. Install on the **same machine that runs Apache** (your XAMPP PC).

#### 3.1 Python 3

1. Download: [python.org/downloads](https://www.python.org/downloads/) (64-bit recommended).
2. Run the installer.
3. On the first screen, check **“Add python.exe to PATH”** (or “Add Python to PATH”).
4. Choose **Install Now** (or customize if you prefer a fixed path).

Verify in **Command Prompt** (new window after install):

```bat
python --version
```

If `python` is not found, try:

```bat
py -3 --version
```

The app tries `python`, then `py -3`, then common install paths automatically.

#### 3.2 Python packages (pip)

Open **Command Prompt** and run:

```bat
pip install pdfplumber openpyxl PyMuPDF pytesseract pdf2image
```

| Package | Used by |
|---------|---------|
| `pdfplumber` | PDF → Excel (text layer per page) |
| `PyMuPDF` (`fitz`) | Waybill Stamper (read/stamp PDF) |
| `openpyxl` | Excel read/write (stamper, PDF→Excel, order remarks) |
| `pytesseract` | OCR wrapper (stamper + PDF→Excel fallback) |
| `pdf2image` | Renders PDF pages to images for OCR |

Verify imports:

```bat
python -c "import pdfplumber, fitz, openpyxl, pytesseract, pdf2image; print('All OK')"
```

If you use `py` instead of `python`:

```bat
py -3 -m pip install pdfplumber openpyxl PyMuPDF pytesseract pdf2image
py -3 -c "import pdfplumber, fitz, openpyxl, pytesseract, pdf2image; print('All OK')"
```

#### 3.3 Tesseract OCR

Needed when a waybill page has **no text layer** (some Lazada/Shopee scans) or PDF → Excel cannot read the tracking number from text.

1. Download the Windows installer: [UB Mannheim Tesseract wiki](https://github.com/UB-Mannheim/tesseract/wiki).
2. Install to the default folder: `C:\Program Files\Tesseract-OCR\`
3. Optional: check “Add to PATH” during install. The app also checks this path even if PATH is not set for Apache.

Confirm the executable exists:

`C:\Program Files\Tesseract-OCR\tesseract.exe`

Or in CMD:

```bat
tesseract --version
```

**Custom install path:** copy `waybill-stamper/paths.sample.json` to `waybill-stamper/paths.json` and set the full path to `tesseract.exe`.

#### 3.4 Poppler (for pdf2image)

`pdf2image` needs Poppler’s `pdftoppm` to turn PDF pages into images before OCR.

1. Download a Windows build: [poppler-windows releases](https://github.com/oschwartz10612/poppler-windows/releases).
2. Extract the ZIP (e.g. to `C:\poppler\`).
3. Add the **bin** folder to your system PATH, for example:
   - `C:\poppler\Library\bin`
   - (Exact folder name depends on the release; look for `pdftoppm.exe` inside.)

Verify:

```bat
pdftoppm -h
```

**Custom path:** set `"poppler": "C:\\poppler\\Library\\bin"` in `waybill-stamper/paths.json` (see `paths.sample.json`).

#### 3.5 PHP: allow Python and large uploads

Edit `C:\xampp\php\php.ini`, then **restart Apache** in XAMPP.

**Enable `shell_exec`** (required for Python jobs):

- Find `disable_functions`.
- Remove `shell_exec` from that list (leave other restrictions if your policy requires them).

**Upload / runtime limits** (per-file uploads are ~60 MB max in the UI; raise if needed):

```ini
upload_max_filesize = 64M
post_max_size = 68M
max_execution_time = 300
```

`api/v1/.htaccess` may also raise limits for API routes when Apache allows `.htaccess` overrides.

#### 3.6 Optional path overrides

If Tesseract or Poppler are not on PATH (common when Apache runs as a service):

```bat
copy waybill-stamper\paths.sample.json waybill-stamper\paths.json
```

Edit `paths.json`:

```json
{
  "tesseract": "C:\\Program Files\\Tesseract-OCR\\tesseract.exe",
  "poppler": "C:\\poppler\\Library\\bin"
}
```

PDF → Excel reads the same `waybill-stamper/paths.json` for OCR fallback.

#### 3.7 Quick checklist

| Step | Command / check |
|------|------------------|
| Python | `python --version` |
| pip packages | `python -c "import pdfplumber, fitz, openpyxl, pytesseract, pdf2image; print('OK')"` |
| Tesseract | `tesseract --version` or file exists under `Program Files\Tesseract-OCR` |
| Poppler | `pdftoppm -h` or `paths.json` |
| PHP | `shell_exec` not disabled; Apache restarted |
| App | Log in → **Waybill → PDF to Excel** or **Stamper** and run a small test PDF |

#### 3.8 Troubleshooting

| Problem | What to do |
|---------|------------|
| “Python is not available” | Install Python, add to PATH, restart Apache; try `py -3` in CMD |
| `pip` not found | `python -m pip install …` or reinstall Python with pip enabled |
| OCR fails / empty tracking | Install Tesseract + Poppler; set `paths.json` |
| `pdf2image` / Poppler error | Add `Library\bin` to PATH or `paths.json` → `poppler` |
| Upload too large | Increase `upload_max_filesize` / `post_max_size` in `php.ini` |
| Processing timeout | Increase `max_execution_time`; large OCR batches take minutes |
| `shell_exec` disabled | Remove from `disable_functions` in `php.ini` |

More detail: [`waybill-stamper/SETUP.md`](waybill-stamper/SETUP.md), [`pdf-to-excel/README.md`](pdf-to-excel/README.md).

### 4. React SPA

```bash
cd app
npm install
npm run dev          # http://localhost:5173 — proxies /rts/api to XAMPP
npm run build:prod   # output → app/dist/ for Apache
```

### Verify script (agent loop + local QA)

After code changes, run the unified verify harness (used by `.cursor/agents/verifier` and hooks):

```powershell
powershell -File tools/verify.ps1              # scoped by git diff
powershell -File tools/verify.ps1 -All         # all stages
powershell -File tools/verify.ps1 -WithApi     # include API smoke (XAMPP up)
```

Stages: PHP syntax (`includes/`, `api/`), parcel tier tests, SPA build (when `app/` touched), memory bank regenerate (when PHP API touched), optional API smoke.

Implementation prompt for approved plans:

```text
Implement <plan>. Sub-agent loop: verifier after each batch; fixer on FAIL; max 5 cycles.
```

### 5. Config

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
| **Waybill / CSR tools** | With `config.php` `storage.root` set (recommended): `rts_storage/waybill_stamper/`, `pdf_to_excel/`, `order_remarks/`, `online_orders/` — stashes & outputs stay outside the git repo. Legacy fallback: `storage/*` inside project when `storage.root` is empty. |

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
| `online_orders.php` | CSR Online Orders batch wizard (partner / VIP / Shopee SPX) |
| `waybill.php` | Waybill stamper |
| `pdf_to_excel.php` | PDF → Excel (waybill labels → iPYX) |
| `order_remarks.php` | Courier Excel remarks cleanup |
| `dashboard.php`, `edit_requests.php` | Dashboard & corrections |

---

## Planned / future additions

| Item | Status | Notes |
|------|--------|--------|
| **CSR portal expansion** | Planned | `portal.php` is a stub; more CSR self-service pages |
| **Paragis / Cacao-BRC inventory** | Partial | Routes exist; operational parity with Honey v3 |
| **Waybill stamper** | ✅ Shipped | |
| **PDF → Excel** | ✅ Shipped | Review gate before download; Stamper handoff manual |
| **Order remarks** | ✅ Shipped | JNT / SPX / Flash profiles |
| **Legacy → SPA migration** | Ongoing | `record.php` RTS flow still primary for case capture |
| **Team leader / staff SPA** | Partial | Stock input & daily count in SPA; some flows still legacy |
| **Executive legacy pages** | Deprecated path | Prefer SPA dashboard / reports |
| **Dashboard shortcuts** | Planned | Quick links per role (e.g. waybill, RTS sync) |
| **Stronger production deploy** | Planned | Env-based config, CI build, documented PHP/Python paths |
| **Automated tests** | Planned | API smoke tests, waybill processor fixture runs |

---

## Related docs

- [`docs/Pyx_Tracker_Project_Specification.docx`](docs/Pyx_Tracker_Project_Specification.docx) — project spec (executive / IT)  
- [`docs/Pyx_Tracker_Executive_Guide.docx`](docs/Pyx_Tracker_Executive_Guide.docx) — how the system works (non-technical)  
- [`waybill-stamper/SETUP.md`](waybill-stamper/SETUP.md) — Waybill-focused setup & troubleshooting  
- [`pdf-to-excel/README.md`](pdf-to-excel/README.md) — PDF → Excel API & columns  
- [`app/README.md`](app/README.md) — SPA dev commands  
- [`config/forwarding.sample.php`](config/forwarding.sample.php) — ngrok fields  

Regenerate DOCX files: `py -3 docs/generate_executive_docs.py`

---

## License / internal use

Internal Finest Wellness PH tooling. Do not commit secrets (`config/config.php`, cookies, `.env`) to git.

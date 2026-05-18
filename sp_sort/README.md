# Excel Sorter

Upload 1–3 Excel files, merge them, and sort all rows by Column D (numeric) — then download the result as a sorted .xlsx file.

---

## Requirements

- PHP 7.4 or higher
- Composer (https://getcomposer.org/)
- A web server (Apache, Nginx, or `php -S localhost:8000`)

---

## Setup

### 1. Install dependencies

```bash
cd excel-sorter/
composer install
```

This installs **PhpSpreadsheet** — the only dependency.

### 2. Create required folders

The app creates these automatically on first run, but you can also create them manually:

```bash
mkdir uploads outputs
chmod 755 uploads outputs
```

> On shared hosting, make sure `uploads/` and `outputs/` are **writable** by the web server.

### 3. Run locally (dev)

```bash
php -S localhost:8000
```

Then open: http://localhost:8000

### 4. Deploy to a web server

- Upload all files (including the `vendor/` folder after `composer install`)
- Point your web server's document root to the `excel-sorter/` folder
- Ensure `uploads/` and `outputs/` are writable (`chmod 755` or `777`)

---

## How It Works

1. User uploads 1, 2, or 3 `.xlsx` / `.xls` files
2. All files are read and their rows are merged into a single dataset
3. The merged rows are **sorted numerically by Column D** (entire rows move together)
4. A formatted output `.xlsx` is generated with:
   - Styled header row (dark blue)
   - Zebra-striped data rows
   - Column D values bolded
   - Auto-sized columns
   - Frozen header row
5. User downloads the sorted file (it's auto-deleted from the server after download)

---

## Options

| Option | Description |
|---|---|
| Ascending / Descending | Sort direction for Column D |
| First row is a header | If checked, the header row from the first file is preserved and not sorted |

---

## Notes

- Files are temporarily saved in `uploads/` and deleted after processing
- Output files are deleted from `outputs/` after the user downloads them
- No database required — fully stateless
- Max 3 files per submission

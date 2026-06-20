# 🍯 Honey Order Editor

A PHP web system for cleaning up JNT export remarks.

## Requirements
- PHP 7.4+ with `shell_exec` enabled
- Python 3 with `openpyxl` installed (`pip install openpyxl`)

## Setup

1. Upload all files to your PHP server (Apache/Nginx)
2. Make sure `uploads/` and `output/` folders are writable:
   ```
   chmod 755 uploads/ output/
   ```
3. Place your `orders.xlsx` file in the same folder as a default,
   OR upload it via the web UI

## Usage

1. Open `index.php` in your browser
2. Upload your JNT export `.xlsx` file using the upload bar
3. The system auto-parses each remark and suggests a clean version
4. Use the **quick preset buttons** (LAD, L, A, D, etc.) to one-click fill,
   or manually edit the textarea
5. Click **Save** on each row to mark it done
6. When finished, click **Export Updated Excel** to download the updated file

## How it parses remarks

The system reads `textarea_input_1` (variants like "light, amber, dark") and converts to codes:
- **L** = Light
- **A** = Amber  
- **D** = Dark

Examples:
- `textarea_input_1: light, amber, dark` → `3L-HONEY LAD`
- `textarea_input_1: Amber` → `2L-HONEY AA` (all amber for 2L)
- `textarea_input_1: 2 Dark & 1 Amber` → `3L-HONEY AAD`

## Files
- `index.php` — Main UI
- `api.php` — Backend API (reads xlsx, saves edits, exports)
- `uploads/` — Uploaded files and saved edits
- `output/` — Temp export files

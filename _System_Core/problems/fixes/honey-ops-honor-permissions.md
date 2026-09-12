---
date: 2026-09-12
type: fix
area: auth-permissions
tags: [fix, honey, permissions]
---

# Fix: Honor honey.* permissions; scope presets by home dept

1. **`api/v1/honey.php`** — Honey-stage list actions skip the home-dept honey/csr hard gate (still require `honey.view` via `api_honey_require_view`). TL honey ops no longer require `dept_id` = honey when `honey.ops` is granted. Staff/TL cross-dept **raw line** (`dept`/`slug`/`product_key`) scope remains.
2. **`includes/permissions.php`** — `ims_preset_honey_pipeline()`: TL/staff role presets only auto-include honey pipeline keys for home dept `honey` or `csr`. Paragis/Cacao presets no longer imply Raw Honey access unless an executive grants honey.* explicitly.

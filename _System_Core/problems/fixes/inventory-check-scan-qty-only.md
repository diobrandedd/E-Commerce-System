---
date: 2026-09-18
type: fix
---

# Fix: Check Inventory scan → quantity only

1. Docs optional (`inventory_check_optional_doc_fields`); never required on scan/update.
2. SKU upsert by `(event_id, code)` — rescan adds qty; phase85 unique index.
3. Unique stickers auto-commit +1; SPA pending panel is quantity only.
4. Edit line = qty only; batch/bottling columns removed from active list UI.

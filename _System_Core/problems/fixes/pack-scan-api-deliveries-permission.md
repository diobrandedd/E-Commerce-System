---
date: 2026-08-20
type: fix
tags: [permissions, pack-scan]
---

# Fix: Wire pack scan API to deliveries.pack_scan

- `honey_bottle_can_pack_scan($user)` → `ims_user_can($user, 'deliveries.pack_scan')`
- `api_bottle_require_pack_scan()` used by `pack_start`, `pack_get`, `pack_session_list`, `pack_scan`, `pack_remove_item`, `pack_confirm`, `pack_discard`
- Also allows `honey.bottle.scan` so Honey production users keep access without needing both checkboxes

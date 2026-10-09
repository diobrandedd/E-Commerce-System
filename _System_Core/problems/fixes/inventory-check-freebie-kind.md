---
date: 2026-10-09
type: fix
---

# Fix: Freebie Add-to-list + apply balance

1. Drop Give out from Freebies modal (Create + History).
2. Phase 93: extend kind ENUM with `freebie`.
3. `inventory_check_add_freebie` + API `scan_freebie` (synthetic `FREEBIE:{product_key}`).
4. Compare/apply uses `dept_raw_freebie_on_hand` / sets balance on Confirm.
5. Add-to-list: Scan | Freebie mode chips.

---
status: shipped
created: 2026-09-11
owner: ops
area: inventory
tags: [inventory-check, private-sessions, compare, batch]
---

# Inventory check: private sessions + scoped compare

> Each auditor owns a private open session. Optional batch metadata. Compare only audited>0 lines. Full-report / executive can multi-select history and merge-compare.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[plans/archive/2026-09-11_inventory-check-scoped-compare_plan]] · [[problems/records/2026-09-11_inventory-check-private-sessions]]

## Locked behavior (chat 2026-09-11)

1. Private sessions per `opened_by` (not one shared open event).
2. Batch optional metadata; system match stays variant|size / dept product.
3. Compare emits only keys with audited > 0.
4. Without `inventory.check.view_all`: history = own sessions only.
5. view_all / executive: multi-select closed history → merge compare.

## Success criteria

- [x] Two users can each hold an open session the same day.
- [x] Non–view-all cannot see another user’s session/history.
- [x] Compare has no zero-audited honey sizes.
- [x] Add modal has optional batch; view_all can Compare selected.

---
date: 2026-09-10
type: fix
---

# Fix: sales.view_amounts permission

1. Catalog key `sales.view_amounts`; prod_manager preset includes it.
2. Phase 79 grants amounts to existing `sales.view` holders.
3. API strips summary/checkout/item money when denied; sets `can_view_amounts`.
4. SalesPage hides ₱ lines, AR/Discount cards, Total column, and reprint when denied.

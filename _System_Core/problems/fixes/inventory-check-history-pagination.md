---
date: 2026-10-02
type: fix
area: inventory-check
tags: [fix]
---

# Fix: Paginate audit history and confirmed updates at 10 per page

Both lists use server pagination with `per_page=10` and Previous/Next. Compare-selected keeps IDs across pages (closed-only checkboxes).

→ Problem: [[records/2026-10-02_inventory-check-history-pagination]]

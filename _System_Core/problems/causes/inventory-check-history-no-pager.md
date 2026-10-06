---
date: 2026-10-02
type: cause
area: inventory-check
tags: [cause]
---

# Cause: History UI loaded first page only at 25 without pager

APIs already supported `page` / `per_page`, but the SPA always requested page 1 (applied at 25) and showed a “Showing N of total” note without Previous/Next.

→ Problem: [[records/2026-10-02_inventory-check-history-pagination]]

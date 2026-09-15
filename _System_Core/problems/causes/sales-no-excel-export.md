---
date: 2026-09-15
type: cause
area: sales
---

# Cause: No Sales Excel export endpoint

Sales snapshot API (`honey_sold`) only returned paginated JSON for the SPA table. There was no binary download that combined Walk-in vs Shipped channel share with full checkout / pack-scan row lists, and no RTS-resellable flag on shipped lines.

---
date: 2026-09-18
type: fix
area: sales
tags: [fix]
---

# Fix: Weekly named Sales Excel sheets

## Behavior

1. **Summary** sheet = totals for the selected period (e.g. Sept 1–30 month report).
2. Detail sheets are **Monday–Sunday weeks**, two per week when both channels have data:
   - `SE 14-20-shp` (shipped / pack-scan)
   - `SE 14-20-wlk` (walk-in)
3. Cross-month week: `JN 29-JL 5-shp`.
4. Overflow: `SE 14-20-shp (2)` when a week exceeds 5,000 rows.
5. Multi-year exports append short year on the title when needed for uniqueness.

---
date: 2026-09-18
type: fix
area: sales
tags: [fix]
---

# Fix: Weekly named Sales Excel sheets

## Behavior

1. **One Summary sheet per calendar month** — e.g. `Sep Summary`, `Aug Summary` (add year only if export spans multiple years). Totals for that month only.
2. Each month summary includes **shipped** and **walk-in** blocks with: units + RTS, **by bottle/size**, **by variant**, **by variant × size** (same detail as the old single-sheet header).
3. Detail sheets are **Monday–Sunday weeks**, two per week when both channels have data:
   - `SE 14-20-shp` (shipped / pack-scan)
   - `SE 14-20-wlk` (walk-in)
4. Cross-month week: `JN 29-JL 5-shp`.
5. Overflow: `SE 14-20-shp (2)` when a week exceeds 5,000 rows.
6. Sept 1–30 export → single `Sep Summary` + that month’s weekly sheets.

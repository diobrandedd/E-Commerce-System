---
date: 2026-09-18
type: fix
area: sales
tags: [fix]
---

# Fix: Weekly named Sales Excel sheets

## Behavior

1. **Summary matches the export filter**
   - **Today / This week / This month** (and single-month custom) → one sheet titled **`Summary`** with `Period: Today` (etc.), not a misleading “Sep monthly” rollup.
   - **All time / multi-month** → one sheet per calendar month (`Sep Summary`, `Aug Summary`; year only if multi-year).
2. Each summary includes **shipped** and **walk-in** blocks: units + RTS, **by bottle/size**, **by variant**, **by variant × size**.
3. **Each shp/wlk detail sheet** starts with the same mini summary (size / variant / variant×size for rows on that sheet), then the row detail table.
4. Detail sheets are **Monday–Sunday weeks**, two per week when both channels have data:
   - `SE 14-20-shp` (shipped / pack-scan)
   - `SE 14-20-wlk` (walk-in)
5. Cross-month week: `JN 29-JL 5-shp`.
6. Overflow: `SE 14-20-shp (2)` when a week exceeds 5,000 rows.

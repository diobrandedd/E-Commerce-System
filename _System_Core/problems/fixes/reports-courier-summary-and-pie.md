# Fix: Add courier summary API and courier pie chart in Reports

- Added `reports.php?action=courier_summary` with exclusive filter modes (`date_range`, `month`, `year`) and optional parcel status filtering.
- Normalized courier names into fixed buckets (`J&T Express`, `SPX Express`, `Flash Express`) before aggregation.
- Replaced stock-movement chart block in `ReportsPage.tsx` with courier analytics: filter controls, summary cards, and a pie chart with tooltip count and percentage.
- Kept department snapshot table unchanged for existing inventory context.

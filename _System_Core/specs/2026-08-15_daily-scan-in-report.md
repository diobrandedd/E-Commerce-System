---
date: 2026-08-15
status: approved
area: inventory-reporting
---

# Spec: Daily scan-to-inventory report

## Goal

Raw Honey → Packaging and the barcode panels for Paragis Tea, Black Rice Coffee,
Jar Cacao, and Honey Comb report the gross number of stickers scanned into
inventory on a selected calendar date.

## Decisions

- Default report date is today, even when today has no scans.
- Date selector also lists historical scan dates and **All dates**.
- Counts come from barcode `scanned_at`, not current inventory balances.
- Later pack, POS, defect, RTS, or restock activity never reduces scan-in history.
- Honey groups by packaging batch / variant / size.
- Dept products group by request / batch; row drilldown shows matching stickers,
  scanner, time, and current unit status.
- Existing product pages remain the source of current on-hand inventory.

## Acceptance

- Honey Packaging size columns are gross scan-in totals for the selected date.
- Dept request/history tables show gross scanned counts for the selected date.
- Row drilldowns use the same date filter as their summary.
- All four dept barcode products share the mechanism.
- No database migration is required.

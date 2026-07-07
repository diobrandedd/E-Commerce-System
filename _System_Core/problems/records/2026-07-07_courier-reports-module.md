---
date: 2026-07-07
type: problem-record
status: fixed
area: reports
tags: [problem, reports, courier, analytics]
---

# Problem: Reports module lacked courier shipment analytics

## Symptom

Reports page showed stock movement and department snapshot only, while operations needed courier distribution visibility (J&T, SPX, Flash), summary counts, and period/status filtering in one place.

## Cause

→ [[causes/reports-courier-metrics-missing]]

## Fix

→ [[fixes/reports-courier-summary-and-pie]]

## Files

| Role | Path / note |
|------|-------------|
| Primary API | `api/v1/reports.php` |
| Primary SPA | `app/src/app/pages/exec/ReportsPage.tsx` |
| Cause note | `problems/causes/reports-courier-metrics-missing.md` |
| Fix note | `problems/fixes/reports-courier-summary-and-pie.md` |

## Deploy

- Verify complete (`tools/verify.ps1`): PHP syntax PASS, SPA build PASS, memory bank generate PASS.

## Links

- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]
- **Patterns:** [[005_SPA_UX_Patterns#Mobile-first is mandatory]]
- **Plan:** `c:/Users/Admin/.cursor/plans/courier-reports-module_7b8819ce.plan.md`

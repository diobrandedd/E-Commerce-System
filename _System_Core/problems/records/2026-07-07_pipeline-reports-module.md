---
date: 2026-07-07
type: problem-record
status: fixed
area: reports
tags: [problem, reports, pipeline, analytics]
---

# Problem: Reports module lacked pipeline shipment analytics

## Symptom

Reports already showed courier analytics, but operations still had no direct visibility of shipment distribution by source pipeline (Partners, VIP, Shopee SPX, TikTok) under the same date/status filters.

## Cause

→ [[causes/reports-courier-metrics-missing]]

## Fix

→ [[fixes/reports-courier-summary-and-pie]]

## Files

| Role | Path / note |
|------|-------------|
| Primary API | `api/v1/reports.php` |
| Primary SPA | `app/src/app/pages/exec/ReportsPage.tsx` |

## Deploy

- Verify complete (`tools/verify.ps1`): PHP syntax PASS, SPA build PASS, memory bank generate PASS.

## Links

- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]
- **Patterns:** [[005_SPA_UX_Patterns#Mobile-first is mandatory]]
- **Plan:** `c:/Users/Admin/.cursor/plans/pipeline-reports-module_7d4fee84.plan.md`

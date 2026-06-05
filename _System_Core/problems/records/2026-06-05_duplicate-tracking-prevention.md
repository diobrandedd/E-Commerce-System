---
date: 2026-06-05
type: problem-record
status: fixed
area: online-orders
tags: [problem, tracking]
---

# Problem: Duplicate tracking allowed across online order pipelines

## Symptom

Same TN could be saved twice; no clear conflict UI.

## Cause

→ [[causes/duplicate-tracking-across-pipelines]]

## Fix

→ [[fixes/duplicate-tracking-modal-and-409]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/online_orders_generator.php` |
| API | `api/v1/online_orders.php` |
| SPA | `DuplicateTrackingModal.tsx`, `useDuplicateTrackingHandler.ts`, pipeline steps |

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Duplicate tracking conflict (Online Orders)]]
- **Fragile edges:** [[003_Fragile_Edges#Online Orders duplicate tracking]]

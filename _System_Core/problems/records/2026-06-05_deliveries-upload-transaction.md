---
date: 2026-06-05
type: problem-record
status: fixed
area: deliveries
tags: [problem, deliveries, database]
---

# Problem: Deliveries upload failed with no active transaction

## Symptom

Photo taken → Done / Scan next → **"There is no active transaction"**; batch count stayed 0.

## Cause

→ [[causes/ddl-inside-transaction-implicit-commit]]

## Fix

→ [[fixes/ensure-schema-before-upload-transaction]]

## Files

| Role | Path |
|------|------|
| PHP upload | `includes/deliveries_upload.php` |
| API | `api/v1/deliveries.php` |
| SPA | `DeliveriesPage.tsx`, `DeliveriesReviewPage.tsx` |

## Deploy

- PHP + SPA build

## Links

- **Fragile edges:** [[003_Fragile_Edges#Deliveries upload transaction]]

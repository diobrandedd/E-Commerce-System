---
date: 2026-06-04
type: problem-record
status: fixed
area: online-orders
tags: [problem, online-orders, enhancement]
---

# Problem: CSR online order tools were separate silos

## Symptom

No unified session for mixed order types or single master iPYX Excel.

## Cause

→ [[causes/csr-tools-fragmented-across-pages]]

## Fix

→ [[fixes/unified-online-orders-generator-wizard]]

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase17_csr_online_orders.sql` |
| Backend | `includes/online_orders_generator.php` |
| API | `api/v1/online_orders.php` |
| SPA | `OnlineOrdersGeneratorPage.tsx` |

## Links

- **Plan:** [[plans/archive/2026-06-04_Online-Orders-Generator_plan]]

---
date: 2026-06-04
type: problem-record
status: fixed
area: online-orders
tags: [problem, online-orders]
---

# Problem: Online Orders E2E QA failures (500s, upload, variant, download)

## Symptom

Partner 500, Shopee upload errors, skipped stamp download, variant picker skipped, VIP poor upload UX.

## Cause

→ [[causes/online-orders-e2e-qa-surface-issues]]

## Fix

→ [[fixes/online-orders-post-ship-qa-fixes]]

## Files

| Role | Path |
|------|------|
| PHP | `includes/online_orders_generator.php` |
| Python | `pdf-to-excel/extractor.py` |
| SPA | `ShopeeSpxRunStep.tsx`, `PartnerRunStep.tsx`, `VipRunStep.tsx`, `StampResultPanel.tsx` |

## Deploy

- PHP + Python + SPA build

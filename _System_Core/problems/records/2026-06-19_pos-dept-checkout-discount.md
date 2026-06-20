---
date: 2026-06-19
type: problem-record
status: fixed
area: pos
tags: [problem]
---

# Problem: Dept POS checkout broken; no discount; oversized cart UI

## Symptom

Paragis Tea and other dept category products could be added to cart but checkout failed. Honey had 10%/20% executive discount approval; dept products did not. Cart section on dept product page was large and full-width.

## Cause

→ [[causes/pos-dept-modal-missing-open-prop]]

Dept checkout modal used `POSModal` without `open={true}` so confirm dialog never rendered. Discount approval pipeline only called `honey_pos_checkout()` on approve.

## Fix

→ [[fixes/pos-dept-checkout-discount-ui]]

Extended `dept_pos_checkout()` with discount opts; `pos_discount_quote()` / approve branch for dept carts via `product_key`. `POSDeptPage` aligned with Honey: compact centered cart, fixed checkout bar, discount request + polling, `product_id` on cart lines.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/dept_pos.php` |
| Primary | `includes/pos_discount_requests.php` |
| SPA | `app/src/app/pages/pos/POSDeptPage.tsx` |
| SPA | `app/src/app/lib/posDept.ts` |

## Deploy

`cd app && npm run build` + hard refresh; PHP includes auto-deploy with repo.

## Links

- **Parent:** POS dept routing fix [[records/2026-06-19_pos-dept-catalog-route]]

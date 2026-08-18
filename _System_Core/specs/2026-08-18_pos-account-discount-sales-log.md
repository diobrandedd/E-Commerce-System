---
date: 2026-08-18
status: shipped
area: pos-sales
---

# Spec: Account POS, no discount approval, walk-in sales log

## Goal

1. **POS on staff accounts** — `/pos` is granted with permission `pos.sell` (executives always). Dedicated PIN login still works.
2. **No discount request** — cashier applies 10%/20% at checkout; the receipt stores the discount. No executive approval queue.
3. **Walk-in sales log** — Sales tab **Recent walk-in sales** shows who sold, who bought, and discount. Filter by product or All.

## POS

- Gate: IMS `pos.sell` **or** POS PIN session.
- Checkout writes `pos_sales.sold_by_user_id` (IMS) or `sold_by_pin_id` (PIN).
- 10%/20% buttons complete the sale immediately (same as full price).
- Exit from IMS POS returns to the dashboard (does not log out). PIN Exit still logs out.

## Sales tab

- Recent list columns include **Sold by** and **Sold to** (buyer).
- Discount % still shown on discounted rows.
- Product filter: All, Honey, Paragis, Black Rice, Cacao, Honey Comb (match any line on the receipt).

## Out of scope

- Removing PIN login or `pos_pins` settings.
- Auto-granting `pos.sell` to every existing staff row (grant in Staff permissions).
- Changing AR checkout rules.

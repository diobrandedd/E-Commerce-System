---
status: approved
created: 2026-10-09
approved_by: chat
---

# Feature Spec: Inventory check — Freebie samples

## Problem

Freebie sample pools (Paragis / Black Rice / Cacao) have no barcode path, so they cannot enter Inventory Check via scan. Ops need to count freebies and sync `dept_raw_freebie_balances` on Confirm. Give out on the Freebies modal is unnecessary once audit can correct counts.

## Goals

1. Remove **Give out** from Freebies modal (Create + History only).
2. Add-to-list: **Freebie** mode — pick product + qty (no barcode).
3. Compare/apply uses freebie on-hand; Confirm sets balance to counted qty.

## Non-goals

- Freebie barcodes / unique stickers
- Give-out API removal (may remain unused)
- Changing honey/dept scan flows

## Acceptance

- Freebies modal has no Give out.
- Auditor can add Freebie · Paragis / Black Rice / Cacao with a qty.
- Compare shows system vs counted; Confirm updates freebie on-hand.

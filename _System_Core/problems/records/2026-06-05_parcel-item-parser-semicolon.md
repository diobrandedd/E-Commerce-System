---
date: 2026-06-05
type: problem-record
status: fixed
area: parcels
tags: [problem, parcels, parser]
---

# Problem: Mixed parcel lines parsed honey qty incorrectly

## Symptom

`3 Paragis Tea ; 1 Liter Honey AMBER` parsed as 3× amber liter.

## Cause

→ [[causes/parcel-parser-no-semicolon-split]]

## Fix

→ [[fixes/semicolon-split-and-other-items]]

## Files

| Role | Path |
|------|------|
| Parser | `includes/honey_parcel_item_parser.php` |
| Promote | `includes/online_order_parcels.php` |
| SPA | `OnlineParcelDetailModal.tsx` |

## Deploy

- PHP + SPA; re-finalize batches for existing wrong `items_parsed_json`

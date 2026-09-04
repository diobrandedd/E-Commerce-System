---
date: 2026-09-04
type: problem-record
status: fixed
area: pack-scan
tags: [problem, honey, unique-barcode, packaging]
---

# Problem: Unique stickers fail pack confirm with empty linked batch

## Symptom

Pack scan with only unique codes (e.g. Dark/Amber/Light 1L stickers) shows “Not enough packaging stock to deduct this bottle” on Done scanning, while Products still shows bottled stock.

## Cause

→ [[causes/unique-pack-linked-batch-empty]]

## Fix

→ [[fixes/unique-pack-fifo-fallback]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_deduct_packaging_one`) |
| Related | [[problems/records/2026-09-04_pack-confirm-mixed-sku-unique]] |
| Related | [[generated/modules/honey_bottle_barcodes]] |

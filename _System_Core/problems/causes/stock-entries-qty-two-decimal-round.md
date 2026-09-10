---
date: 2026-09-10
type: cause
area: inventory
tags: [cause]
---

# Cause: stock_entries.qty DECIMAL(12,2) rounds pouch bag fractions

Paragis bag-pool deducts **0.048 bags** (48 g leaf / 1000 g bag). MySQL `DECIMAL(12,2)` stores that as **0.05**, so each pouch scan permanently removed **50 g** instead of **48 g**.

---
date: 2026-09-10
type: cause
---

# Cause: Inventory check forced packaging batch on every scan

`inventory_check_scan` required `batch_no` for SKU and unique paths, and honey compare keyed by packaging row / batch instead of finished-goods variant+size aggregates.

---
date: 2026-08-26
type: cause
tags: [cause]
---

# Cause: Sold restock only wrote qty −1 sales log

Pack cancel restock restored stock and logged −1 honey qty but never deleted/reduced `pos_receipt_items` or recalculated `pos_sales.total_amount`.

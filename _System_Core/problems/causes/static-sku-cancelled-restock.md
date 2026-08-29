---
type: cause
area: fulfillment
tags: [cancelled-restock, sku, inventory]
---

# Cause: Static SKU returns had no quantity or destination record

Permanent SKU barcodes represent a product, not one physical unit. The cancelled-restock
session only had unit-linked honey and department tables, so a static SKU scan could not
record how many items were returned or whether they belonged in Packaging/Finished stock
or RTS. Treating a static code as a unique sticker would create false unit history and
could not preserve the operator, quantity, or selected inventory pool.

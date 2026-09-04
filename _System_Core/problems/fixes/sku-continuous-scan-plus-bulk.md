# Fix: Continuous SKU +1 with optional typed +N

- Pack confirm session SKU lines and inventory-check SKU rows **increment** qty on re-scan (cap enforced).
- Pack scan, packaging scan-to-inventory, and inventory check: no blocking qty modal; each SKU scan commits +1; sticky Quantity + Add/Stock in adds +N against the last SKU.
- Unique leftover stickers unchanged (one unit per scan). POS qty prompt unchanged.

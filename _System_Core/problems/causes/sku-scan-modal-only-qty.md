# Cause: SKU scan UX forced one scan then a blocking qty modal

Permanent SKU barcodes are identical across bottles. The shipped flow opened a qty modal after the first scan and paused the wedge field, so operators could not scan-through a pile of the same sticker. Pack session and inventory-check backends also **replaced** qty on re-scan instead of incrementing, which blocked continuous counting even if the UI had allowed it.

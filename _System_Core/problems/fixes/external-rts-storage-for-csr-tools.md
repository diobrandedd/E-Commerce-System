---
type: fix
tags: [fix, storage, infrastructure]
---

# Fix: External rts_storage for CSR tool buckets

## What we changed

`rts_storage_csr_tool_root()` when `config.php` `storage.root` set. Buckets: waybill_stamper, pdf_to_excel, order_remarks, online_orders.

## Related problems

- [[records/2026-06-04_csr-waybill-external-storage]]

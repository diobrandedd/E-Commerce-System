# Fix: Require batch + bottling date; expandable batch docs on compare

1. API: `inventory_check_require_doc_fields`; scan/update require both; docs never drive matching.
2. Phase 81: unique `(event_id, code, batch_no, bottling_date)` for SKU lines.
3. Compare: `batches[]` from scans; SPA expandable groups for owner and view_all/executive.
4. UX: scan → pending docs panel → Confirm line (prefills last batch/date).

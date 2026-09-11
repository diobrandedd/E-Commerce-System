-- Phase 81: allow multiple documented batch/date lines per SKU code in an audit.
-- Unique stickers still enforced in app as one row per code.

ALTER TABLE `inventory_check_scans`
  DROP INDEX `uq_inv_check_scan_code`,
  ADD UNIQUE KEY `uq_inv_check_scan_sku_doc` (`event_id`, `code`, `batch_no`, `bottling_date`);

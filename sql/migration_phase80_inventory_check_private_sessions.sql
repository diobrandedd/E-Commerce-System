-- Phase 80: private per-user inventory check sessions
-- Allow multiple events on the same count_date (one open per user enforced in app).

ALTER TABLE `inventory_check_events`
  DROP INDEX `uq_inv_check_date`,
  ADD KEY `idx_inv_check_opener_status` (`opened_by`, `status`),
  ADD KEY `idx_inv_check_date` (`count_date`);

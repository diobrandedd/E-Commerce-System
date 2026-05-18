-- RTS claim status tracking (updated from Records view only)
USE `rts_system`;

ALTER TABLE `rts_cases`
  ADD COLUMN `claim_status` ENUM(
    'for_submission',
    'denied_claims',
    'approved_claims',
    'submitted_claims',
    'on_process'
  ) NOT NULL DEFAULT 'for_submission' AFTER `damaged_tag`,
  ADD COLUMN `claim_status_note` VARCHAR(512) NOT NULL DEFAULT '' AFTER `claim_status`,
  ADD KEY `idx_claim_status` (`claim_status`);

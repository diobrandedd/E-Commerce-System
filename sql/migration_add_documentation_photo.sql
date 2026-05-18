-- Run this on existing databases that already have rts_cases.
USE `rts_system`;

ALTER TABLE `rts_cases`
  ADD COLUMN `documentation_photo` VARCHAR(190) NULL COMMENT 'filename in assets/rts_documentation' AFTER `date_opened`;

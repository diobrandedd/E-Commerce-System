-- Multi-file RTS proof (photos + videos) + human-readable damaged tag for records.
-- Run on existing databases after prior RTS migrations.

ALTER TABLE `rts_cases`
  ADD COLUMN `documentation_attachments` JSON NULL COMMENT '[{kind:image|video,file:name}]' AFTER `documentation_photo`;

ALTER TABLE `rts_cases`
  ADD COLUMN `damaged_tag` VARCHAR(128) NOT NULL DEFAULT 'no' COMMENT 'no | yes - Leak etc.' AFTER `damaged_reason`;

UPDATE `rts_cases` SET `damaged_tag` = 'no' WHERE `damaged_reason` IS NULL;
UPDATE `rts_cases` SET `damaged_tag` = 'yes - Leak' WHERE `damaged_reason` = 'leak';
UPDATE `rts_cases` SET `damaged_tag` = 'yes - Item replaced' WHERE `damaged_reason` = 'item_replaced';
UPDATE `rts_cases` SET `damaged_tag` = 'yes - Packaging replaced' WHERE `damaged_reason` = 'packaging_replaced';

UPDATE `rts_cases`
SET `documentation_attachments` = JSON_ARRAY(JSON_OBJECT('kind', 'image', 'file', `documentation_photo`))
WHERE `documentation_photo` IS NOT NULL
  AND TRIM(`documentation_photo`) != ''
  AND (`documentation_attachments` IS NULL OR JSON_LENGTH(`documentation_attachments`) = 0);

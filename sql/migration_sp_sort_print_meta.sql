-- SP Sort: print layout meta + prepared-by name on export
USE `rts_system`;

ALTER TABLE `sp_sort_batches`
  ADD COLUMN `print_meta` JSON NULL COMMENT 'order/pickup date lines, notes' AFTER `source_files`,
  ADD COLUMN `prepared_by` VARCHAR(120) NULL AFTER `print_meta`;

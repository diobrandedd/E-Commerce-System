-- Partner seller (Ian/Jono/Joven), TikTok pipeline, wider courier column.
-- Run once: mysql -u root rts_system < sql/migration_phase48_online_orders_partner_seller_tiktok.sql

SET NAMES utf8mb4;

ALTER TABLE `csr_online_batch_runs`
  MODIFY COLUMN `pipeline` ENUM('partner','vip','shopee_spx','tiktok') NOT NULL;

ALTER TABLE `csr_online_order_lines`
  MODIFY COLUMN `source_pipeline` ENUM('partner','vip','shopee_spx','tiktok') NOT NULL,
  MODIFY COLUMN `courier` VARCHAR(191) NOT NULL DEFAULT '';

ALTER TABLE `csr_online_order_lines`
  ADD COLUMN `partner_seller` ENUM('ian','jono','joven') NULL DEFAULT NULL AFTER `source_pipeline`;

ALTER TABLE `online_order_parcels`
  MODIFY COLUMN `source_pipeline` ENUM('partner','vip','shopee_spx','tiktok') NOT NULL,
  MODIFY COLUMN `courier` VARCHAR(191) NOT NULL DEFAULT '';

ALTER TABLE `online_order_parcels`
  ADD COLUMN `partner_seller` ENUM('ian','jono','joven') NULL DEFAULT NULL AFTER `source_pipeline`;

ALTER TABLE `csr_online_order_lines`
  ADD KEY `idx_csr_lines_partner_seller` (`partner_seller`),
  ADD KEY `idx_csr_lines_pipeline_created` (`source_pipeline`, `created_at`),
  ADD KEY `idx_csr_lines_courier` (`courier`(64));

ALTER TABLE `online_order_parcels`
  ADD KEY `idx_parcels_partner_seller` (`partner_seller`),
  ADD KEY `idx_parcels_pipeline_created` (`source_pipeline`, `created_at`),
  ADD KEY `idx_parcels_courier` (`courier`(64));

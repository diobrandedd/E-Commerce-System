-- SKU barcode hex color format (2026-09). Legacy text codes still scan via alias map in sku_barcodes.php.
-- Run once: mysql -u root rts_system < sql/migration_phase73_sku_hex_codes.sql

SET NAMES utf8mb4;

UPDATE product_sku_barcodes SET code = 'F60000' WHERE kind = 'honey' AND variant = 'light' AND size = 'liter';
UPDATE product_sku_barcodes SET code = 'FFBF00' WHERE kind = 'honey' AND variant = 'amber' AND size = 'liter';
UPDATE product_sku_barcodes SET code = '060000' WHERE kind = 'honey' AND variant = 'dark' AND size = 'liter';
UPDATE product_sku_barcodes SET code = 'F54927' WHERE kind = 'honey' AND variant = 'light' AND size = '350ml';
UPDATE product_sku_barcodes SET code = '473600' WHERE kind = 'honey' AND variant = 'amber' AND size = '350ml';
UPDATE product_sku_barcodes SET code = '023020' WHERE kind = 'honey' AND variant = 'dark' AND size = '350ml';
UPDATE product_sku_barcodes SET code = 'FFA259' WHERE kind = 'honey' AND variant = 'light' AND size = '250ml';
UPDATE product_sku_barcodes SET code = 'C62828' WHERE kind = 'honey' AND variant = 'amber' AND size = '250ml';
UPDATE product_sku_barcodes SET code = '1B1212' WHERE kind = 'honey' AND variant = 'dark' AND size = '250ml';
UPDATE product_sku_barcodes SET code = 'F08B51' WHERE kind = 'honey' AND variant = 'light' AND size = 'gallon';
UPDATE product_sku_barcodes SET code = '621F04' WHERE kind = 'honey' AND variant = 'amber' AND size = 'gallon';
UPDATE product_sku_barcodes SET code = '1F150C' WHERE kind = 'honey' AND variant = 'dark' AND size = 'gallon';
UPDATE product_sku_barcodes SET code = '00FF00' WHERE kind = 'dept' AND product_key = 'pt';
UPDATE product_sku_barcodes SET code = '7F00FF' WHERE kind = 'dept' AND product_key = 'brc';
UPDATE product_sku_barcodes SET code = '964B00' WHERE kind = 'dept' AND product_key = 'jar_cacao';
UPDATE product_sku_barcodes SET code = 'E37434' WHERE kind = 'dept' AND product_key = 'honey_comb';

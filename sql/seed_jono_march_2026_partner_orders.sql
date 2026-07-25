-- jono partner seller - March 2026 historical partner orders
-- 23 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-03-01 .. 2026-03-31
-- Run on production: mysql -u USER -p rts_system < sql/seed_jono_march_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'jono'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('1077464474776808', '1085986367280767', '1085978539604359', '1077400214764399', '1085968776431724', '1077653869144288', '1078864452320996', '1079134099595773', '1079502649207642', '1088495190216179', '1088599535272168', '1089022929461808', '1088920369288802', '1089022929461808D2', '1089116345083330', '1089770700427587', '1081532805484820', '1081612491344310', '1090254903096224', '1081961265507780', '1090586966532100', '1082209272778492', '1082272631478947');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '1077464474776808' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'carlito balico' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1077464474776808' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '1085986367280767' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Mai Pantojan' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085986367280767' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '1085978539604359' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'mechille albaran' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085978539604359' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '1077400214764399' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Rube jean T Agudo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1077400214764399' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '1085968776431724' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Maria Leyda Gaerlan' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085968776431724' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '1077653869144288' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Flordeliza Magahin' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1077653869144288' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-10' AS ship_date, '2026-03-10 12:00:00' AS created_at
UNION ALL
SELECT '1078864452320996' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Roy P. Dela Rosa' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1078864452320996' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-16' AS ship_date, '2026-03-16 12:00:00' AS created_at
UNION ALL
SELECT '1079134099595773' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Reymond Patricio' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1079134099595773' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-17' AS ship_date, '2026-03-17 12:00:00' AS created_at
UNION ALL
SELECT '1079502649207642' AS tracking_number, '1 Gallon LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Asher Yaron' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1079502649207642' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-18' AS ship_date, '2026-03-18 12:00:00' AS created_at
UNION ALL
SELECT '1088495190216179' AS tracking_number, '3 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Nena C. Lee' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1088495190216179' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-20' AS ship_date, '2026-03-20 12:00:00' AS created_at
UNION ALL
SELECT '1088599535272168' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Lenlouis Ventigan' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1088599535272168' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-21' AS ship_date, '2026-03-21 12:00:00' AS created_at
UNION ALL
SELECT '1089022929461808' AS tracking_number, '1 Gallon Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jaiden Batuyong' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1089022929461808' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-23' AS ship_date, '2026-03-23 12:00:00' AS created_at
UNION ALL
SELECT '1088920369288802' AS tracking_number, '1.0' AS items_raw, NULL AS items_parsed_json, 'Editha Q. Arce' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1088920369288802' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-23' AS ship_date, '2026-03-23 12:00:00' AS created_at
UNION ALL
SELECT '1089022929461808D2' AS tracking_number, '1.0' AS items_raw, NULL AS items_parsed_json, 'Jaiden Batuyong' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1089022929461808' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-23' AS ship_date, '2026-03-23 12:00:00' AS created_at
UNION ALL
SELECT '1089116345083330' AS tracking_number, '1 250mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Maria Victoria M. Tamayo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1089116345083330' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-24' AS ship_date, '2026-03-24 12:00:00' AS created_at
UNION ALL
SELECT '1089770700427587' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Rosalinda Estapia' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1089770700427587' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-26' AS ship_date, '2026-03-26 12:00:00' AS created_at
UNION ALL
SELECT '1081532805484820' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gary Hernandez' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1081532805484820' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '1081612491344310' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Analiza.sanchez anacan' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1081612491344310' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '1090254903096224' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'Maria Grace Lim' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1090254903096224' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '1081961265507780' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Pamela P.Magno' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1081961265507780' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '1090586966532100' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Mary Ann yangco' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1090586966532100' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '1082209272778492' AS tracking_number, '7 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":7}],"other_items":[]}' AS items_parsed_json, 'Dave villarosa' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1082209272778492' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '1082272631478947' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ervi Evangeline Zamora-Leyco' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1082272631478947' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

-- jono partner seller - April 2026 historical partner orders
-- 54 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-04-01 .. 2026-04-30
-- Run on production: mysql -u USER -p rts_system < sql/seed_jono_april_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'jono'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('1082333454020031', '1082397091305642', '1082426040345148', '1082478429344653', '1091679392574391', '1083158262206125', '1083173801192841', '1091890144688810', '1083363228616790', '1083519202523770', '1092572136688488', '1084001215232154', '1084057052859404', '1084269860124479', '1092911378812105', '1084450639670175', '1084466891407386', '1093160798034355', '1084532400380813', '1084524425992038', '1093171993411301', '1084578262696483', '1084582234624337', '1084696436908421', '1093390337516808', '1084808222144667', '1084811825960184', '1085039409444255', '1085181018276465', '1085462406126498', '1094357557636941', '1094487513899577', '1094583704662786', '1085983488267462', '1085181018276465D2', '1094790385844161', '1094806547744161', '1094947117893209', '1094683711594747', '1086691403673936', '1095702146003859', '1086999001910702', '1095509994795950', '1096201354624242', '1096222956009052', '1096291194990547', '1096400565765988', '1096511339424272', '1096613122955109', '1096632343200034', '1096749761760456', '1096840797544704', '1088287202262173', '1096951160348948');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '1082333454020031' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Patricia Monique Casi' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1082333454020031' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-01' AS ship_date, '2026-04-01 12:00:00' AS created_at
UNION ALL
SELECT '1082397091305642' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Eveline Odenthal/Lolit Bangaysiso' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1082397091305642' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-01' AS ship_date, '2026-04-01 12:00:00' AS created_at
UNION ALL
SELECT '1082426040345148' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'alma cuysona' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1082426040345148' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-01' AS ship_date, '2026-04-01 12:00:00' AS created_at
UNION ALL
SELECT '1082478429344653' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'augusto matacsil' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1082478429344653' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-01' AS ship_date, '2026-04-01 12:00:00' AS created_at
UNION ALL
SELECT '1091679392574391' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Diana Rose A. Abne' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1091679392574391' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1083158262206125' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Roman M. Urbano' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1083158262206125' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1083173801192841' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rommel Malacad' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1083173801192841' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1091890144688810' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'placido Ali.asag' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1091890144688810' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1083363228616790' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Josephine Jamon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1083363228616790' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1083519202523770' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Vicky Azuelo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1083519202523770' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-06' AS ship_date, '2026-04-06 12:00:00' AS created_at
UNION ALL
SELECT '1092572136688488' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Pamela Yee' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092572136688488' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-09' AS ship_date, '2026-04-09 12:00:00' AS created_at
UNION ALL
SELECT '1084001215232154' AS tracking_number, '1 250mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'caroline pacho' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084001215232154' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-09' AS ship_date, '2026-04-09 12:00:00' AS created_at
UNION ALL
SELECT '1084057052859404' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Roselon Cortez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084057052859404' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-09' AS ship_date, '2026-04-09 12:00:00' AS created_at
UNION ALL
SELECT '1084269860124479' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Reden Artugue' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1084269860124479' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-10' AS ship_date, '2026-04-10 12:00:00' AS created_at
UNION ALL
SELECT '1092911378812105' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Pogz Sindian' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092911378812105' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-10' AS ship_date, '2026-04-10 12:00:00' AS created_at
UNION ALL
SELECT '1084450639670175' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Anamarie Briones' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084450639670175' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-11' AS ship_date, '2026-04-11 12:00:00' AS created_at
UNION ALL
SELECT '1084466891407386' AS tracking_number, '2 Liter Honey AMBER, DARK' AS items_raw, NULL AS items_parsed_json, 'Peter Jay Gonzales' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084466891407386' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-11' AS ship_date, '2026-04-11 12:00:00' AS created_at
UNION ALL
SELECT '1093160798034355' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Benedicto F Barroso II' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093160798034355' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-11' AS ship_date, '2026-04-11 12:00:00' AS created_at
UNION ALL
SELECT '1084532400380813' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Maricel M. Laforteza' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1084532400380813' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-11' AS ship_date, '2026-04-11 12:00:00' AS created_at
UNION ALL
SELECT '1084524425992038' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Marivien  Tiglao' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084524425992038' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-11' AS ship_date, '2026-04-11 12:00:00' AS created_at
UNION ALL
SELECT '1093171993411301' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ronald L Patterson' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093171993411301' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1084578262696483' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ruben Elizaga' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084578262696483' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1084582234624337' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Omar Tallod' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084582234624337' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1084696436908421' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'carina rosario' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084696436908421' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1093390337516808' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Maria Julieta Cabral Roca' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093390337516808' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1084808222144667' AS tracking_number, '1 250mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Theresa Joy Delizo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1084808222144667' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1084811825960184' AS tracking_number, '2 Liter Honey AMBER, DARK' AS items_raw, NULL AS items_parsed_json, 'Sunny Pyo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1084811825960184' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-13' AS ship_date, '2026-04-13 12:00:00' AS created_at
UNION ALL
SELECT '1085039409444255' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Evelyn Dones' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1085039409444255' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-14' AS ship_date, '2026-04-14 12:00:00' AS created_at
UNION ALL
SELECT '1085181018276465' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Argelita mendoza' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085181018276465' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-15' AS ship_date, '2026-04-15 12:00:00' AS created_at
UNION ALL
SELECT '1085462406126498' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Alma Fe G. Javelosa' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085462406126498' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-16' AS ship_date, '2026-04-16 12:00:00' AS created_at
UNION ALL
SELECT '1094357557636941' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Juvielyn Dimakuta' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094357557636941' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-17' AS ship_date, '2026-04-17 12:00:00' AS created_at
UNION ALL
SELECT '1094487513899577' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Emily tesorero' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094487513899577' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-18' AS ship_date, '2026-04-18 12:00:00' AS created_at
UNION ALL
SELECT '1094583704662786' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'jed marie pasco' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094583704662786' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-18' AS ship_date, '2026-04-18 12:00:00' AS created_at
UNION ALL
SELECT '1085983488267462' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'lilia C.Torres' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085983488267462' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-18' AS ship_date, '2026-04-18 12:00:00' AS created_at
UNION ALL
SELECT '1085181018276465D2' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Argelita mendoza' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1085181018276465' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-18' AS ship_date, '2026-04-18 12:00:00' AS created_at
UNION ALL
SELECT '1094790385844161' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Vicky Coloma' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094790385844161' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-20' AS ship_date, '2026-04-20 12:00:00' AS created_at
UNION ALL
SELECT '1094806547744161' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Vicky Coloma' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094806547744161' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-20' AS ship_date, '2026-04-20 12:00:00' AS created_at
UNION ALL
SELECT '1094947117893209' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Nilo Glico' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094947117893209' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-20' AS ship_date, '2026-04-20 12:00:00' AS created_at
UNION ALL
SELECT '1094683711594747' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Elizabeth genson' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1094683711594747' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-21' AS ship_date, '2026-04-21 12:00:00' AS created_at
UNION ALL
SELECT '1086691403673936' AS tracking_number, '1 350mL Honey DARK; 1 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Dani Balacoa' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1086691403673936' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-21' AS ship_date, '2026-04-21 12:00:00' AS created_at
UNION ALL
SELECT '1095702146003859' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'cecilia mangahas' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1095702146003859' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-22' AS ship_date, '2026-04-22 12:00:00' AS created_at
UNION ALL
SELECT '1086999001910702' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'nestor de jesus jr.' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1086999001910702' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-22' AS ship_date, '2026-04-22 12:00:00' AS created_at
UNION ALL
SELECT '1095509994795950' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Aureluz Torres' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1095509994795950' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-22' AS ship_date, '2026-04-22 12:00:00' AS created_at
UNION ALL
SELECT '1096201354624242' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Albert Dagal' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096201354624242' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096222956009052' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Prescilda Basalo Custard' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096222956009052' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096291194990547' AS tracking_number, '2 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":2,"raw":"2 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'Evangeline Velano' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096291194990547' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096400565765988' AS tracking_number, '1 Gallon Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jackelyn Ann Gramonte' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096400565765988' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096511339424272' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Joey Vincent G. Palma' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096511339424272' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096613122955109' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'lulu medina' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096613122955109' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096632343200034' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Winnie magpili mejia' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096632343200034' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096749761760456' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Gelo Hao' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096749761760456' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-28' AS ship_date, '2026-04-28 12:00:00' AS created_at
UNION ALL
SELECT '1096840797544704' AS tracking_number, '1 Gallon Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Emmanuel Z. Dela Cruz' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1096840797544704' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-29' AS ship_date, '2026-04-29 12:00:00' AS created_at
UNION ALL
SELECT '1088287202262173' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Kelvin Clark' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1088287202262173' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-29' AS ship_date, '2026-04-29 12:00:00' AS created_at
UNION ALL
SELECT '1096951160348948' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Emma Viray' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1096951160348948' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-04-30' AS ship_date, '2026-04-30 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

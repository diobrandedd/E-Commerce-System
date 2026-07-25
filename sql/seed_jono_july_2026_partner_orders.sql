-- jono partner seller - July 2026 historical partner orders
-- 67 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-07-01 .. 2026-07-31
-- Run on production: mysql -u USER -p rts_system < sql/seed_jono_july_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'jono'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('1110247134868760', '1101849626622870', '1110645744507060', '1110945700268410', '1102259635924740', '1111006995908270', '1102394245577910', '1102506236844470', '1102520205489220', '1111421569536050', '1102854224872300', '1111718548104300', '1103116479604900', '1103154051828170', '1103166410984310', '1111924570476910', '1103366087948110', '1103389466444000', '1103403295143060', '1103431033796230', '1112160131912600', '1103561838332099', '1112335391441451', '1103639073600507', '1103665202364960', '1103760811188330', '1112530156102200', '1112569780788370', '1112622925948040', '1103932201731320', '1104020289948460', '1112757515966300', '1104038234917680', '1112770767178360', '1104120283083640', '1104141296057320', '1104150231858820', '1104258802001970', '1104330494608110', '1113055989592690', '1104450633976870', '1113179344343120', '1104496436004030', '1104498239286480', '1104506460089110', '1104510830474420', '1113314926084310', '1104616079175840', '1104689204548210', '1113442131122070', '1104906073391360', '1104935450934785', '1113697708106765', '1113731376323440', '1113731376323440D2', '1105053097030302', '1105235203806140', '1114019557538990', '1114105520571730', '1114215128697240', '1105534634898340', '1105554406605440', '1105926630452890', '1106055839532100', '1106070238096260', '1114866799816330', '1106373872000028');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '1110247134868760' AS tracking_number, '3 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":3,"raw":"3 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Lorena Beliran' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1110247134868760' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-01' AS ship_date, '2026-07-01 12:00:00' AS created_at
UNION ALL
SELECT '1101849626622870' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Leo Lambatan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101849626622870' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-03' AS ship_date, '2026-07-03 12:00:00' AS created_at
UNION ALL
SELECT '1110645744507060' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Lenard Pangon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1110645744507060' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-03' AS ship_date, '2026-07-03 12:00:00' AS created_at
UNION ALL
SELECT '1110945700268410' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ruby A. de Luna' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1110945700268410' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-06' AS ship_date, '2026-07-06 12:00:00' AS created_at
UNION ALL
SELECT '1102259635924740' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'MARK DUELL' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102259635924740' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-06' AS ship_date, '2026-07-06 12:00:00' AS created_at
UNION ALL
SELECT '1111006995908270' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'NATHANIEL WILKERSON' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1111006995908270' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-06' AS ship_date, '2026-07-06 12:00:00' AS created_at
UNION ALL
SELECT '1102394245577910' AS tracking_number, '3 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Evelyn Costales' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102394245577910' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-06' AS ship_date, '2026-07-06 12:00:00' AS created_at
UNION ALL
SELECT '1102506236844470' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'daisy a. diaz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102506236844470' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-06' AS ship_date, '2026-07-06 12:00:00' AS created_at
UNION ALL
SELECT '1102520205489220' AS tracking_number, '1 Gallon Honey Dark' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'willie dela cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102520205489220' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-07' AS ship_date, '2026-07-07 12:00:00' AS created_at
UNION ALL
SELECT '1111421569536050' AS tracking_number, '2 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Joji Santos' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1111421569536050' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-08' AS ship_date, '2026-07-08 12:00:00' AS created_at
UNION ALL
SELECT '1102854224872300' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Michelle Andaya' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102854224872300' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-08' AS ship_date, '2026-07-08 12:00:00' AS created_at
UNION ALL
SELECT '1111718548104300' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'juvy dulla' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1111718548104300' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-08' AS ship_date, '2026-07-08 12:00:00' AS created_at
UNION ALL
SELECT '1103116479604900' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Erica Abando' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103116479604900' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-09' AS ship_date, '2026-07-09 12:00:00' AS created_at
UNION ALL
SELECT '1103154051828170' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'PETER EGLARINDA' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103154051828170' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-09' AS ship_date, '2026-07-09 12:00:00' AS created_at
UNION ALL
SELECT '1103166410984310' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rodrigo Alibayan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103166410984310' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-09' AS ship_date, '2026-07-09 12:00:00' AS created_at
UNION ALL
SELECT '1111924570476910' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Debby Gumbert' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1111924570476910' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-09' AS ship_date, '2026-07-09 12:00:00' AS created_at
UNION ALL
SELECT '1103366087948110' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Orlando Z. Ochoa Sr' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103366087948110' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-10' AS ship_date, '2026-07-10 12:00:00' AS created_at
UNION ALL
SELECT '1103389466444000' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Jasmin Magat' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103389466444000' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-10' AS ship_date, '2026-07-10 12:00:00' AS created_at
UNION ALL
SELECT '1103403295143060' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Triccie De Leon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103403295143060' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-10' AS ship_date, '2026-07-10 12:00:00' AS created_at
UNION ALL
SELECT '1103431033796230' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'vivian Lizardo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103431033796230' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-10' AS ship_date, '2026-07-10 12:00:00' AS created_at
UNION ALL
SELECT '1112160131912600' AS tracking_number, '3 350mL Honey 1DARK; 1AMBER; 1 LIGHT' AS items_raw, NULL AS items_parsed_json, 'PLT CHERIL L GAROLACAN' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112160131912600' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-10' AS ship_date, '2026-07-10 12:00:00' AS created_at
UNION ALL
SELECT '1103561838332099' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'sonny sarino' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1103561838332099' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-11' AS ship_date, '2026-07-11 12:00:00' AS created_at
UNION ALL
SELECT '1112335391441451' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Jian Claire Ong' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112335391441451' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-11' AS ship_date, '2026-07-11 12:00:00' AS created_at
UNION ALL
SELECT '1103639073600507' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'herman valmocina' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103639073600507' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-11' AS ship_date, '2026-07-11 12:00:00' AS created_at
UNION ALL
SELECT '1103665202364960' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Mila Mae A. Laroza' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103665202364960' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-11' AS ship_date, '2026-07-11 12:00:00' AS created_at
UNION ALL
SELECT '1103760811188330' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'sylvia mariano' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103760811188330' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-11' AS ship_date, '2026-07-11 12:00:00' AS created_at
UNION ALL
SELECT '1112530156102200' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'criscel sese' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112530156102200' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-13' AS ship_date, '2026-07-13 12:00:00' AS created_at
UNION ALL
SELECT '1112569780788370' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Frances Dela Pena' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112569780788370' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-13' AS ship_date, '2026-07-13 12:00:00' AS created_at
UNION ALL
SELECT '1112622925948040' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'baby ortiz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112622925948040' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-13' AS ship_date, '2026-07-13 12:00:00' AS created_at
UNION ALL
SELECT '1103932201731320' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'nher vagay' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103932201731320' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-13' AS ship_date, '2026-07-13 12:00:00' AS created_at
UNION ALL
SELECT '1104020289948460' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Renato Magat' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104020289948460' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1112757515966300' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'jerome dayrit' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112757515966300' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1104038234917680' AS tracking_number, '2 350mL Honey 1LIGHT, 1AMBER' AS items_raw, NULL AS items_parsed_json, 'Cathy Macarat' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104038234917680' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1112770767178360' AS tracking_number, '1 350mL Honey LIGHT; 1 Paragis Tea' AS items_raw, NULL AS items_parsed_json, 'Rita jagolino' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1112770767178360' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1104120283083640' AS tracking_number, '2 350mL Honey 1AMBER, 1DARK' AS items_raw, NULL AS items_parsed_json, 'Manolito Urbina' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104120283083640' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1104141296057320' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Edna Lubrica Hipol' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104141296057320' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1104150231858820' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'syvil napoles' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104150231858820' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-14' AS ship_date, '2026-07-14 12:00:00' AS created_at
UNION ALL
SELECT '1104258802001970' AS tracking_number, '1 Liter Honey DARK; 1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Deo Poblete' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104258802001970' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-15' AS ship_date, '2026-07-15 12:00:00' AS created_at
UNION ALL
SELECT '1104330494608110' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Maricar Rabino' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104330494608110' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-15' AS ship_date, '2026-07-15 12:00:00' AS created_at
UNION ALL
SELECT '1113055989592690' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Bruno Galis' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1113055989592690' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-15' AS ship_date, '2026-07-15 12:00:00' AS created_at
UNION ALL
SELECT '1104450633976870' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'edwin pogi' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104450633976870' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-15' AS ship_date, '2026-07-15 12:00:00' AS created_at
UNION ALL
SELECT '1113179344343120' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Fatima Mohammad' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1113179344343120' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104496436004030' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Terry tenefrancia' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104496436004030' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104498239286480' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Isagani M de Guzman' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104498239286480' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104506460089110' AS tracking_number, '2 350mL Honey LIGHT, DARK' AS items_raw, NULL AS items_parsed_json, 'Joseph Yapcengco Jr.' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104506460089110' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104510830474420' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jorgea C. Baldemor' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104510830474420' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1113314926084310' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rodrigo Alibayan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1113314926084310' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104616079175840' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Kristoffer Ian Catolico' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104616079175840' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1104689204548210' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jeannie Taule' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104689204548210' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-16' AS ship_date, '2026-07-16 12:00:00' AS created_at
UNION ALL
SELECT '1113442131122070' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Willie Gonzales' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1113442131122070' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1104906073391360' AS tracking_number, '3 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":3,"raw":"3 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Joharieh Maglangit' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104906073391360' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1104935450934785' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Chakkrapong Intoh' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104935450934785' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1113697708106765' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Raymond Viñas' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1113697708106765' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1113731376323440' AS tracking_number, '4 Gallon Honey (3Dark, 1 Amber)' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":4}],"other_items":[]}' AS items_parsed_json, 'Jason Katz' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1113731376323440' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1113731376323440D2' AS tracking_number, '2 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":2}],"other_items":[]}' AS items_parsed_json, 'Jason Katz' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1113731376323440' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1105053097030302' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'evelyn manangkil' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1105053097030302' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-17' AS ship_date, '2026-07-17 12:00:00' AS created_at
UNION ALL
SELECT '1105235203806140' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Marilyn Asuncion-Jawile' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1105235203806140' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-19' AS ship_date, '2026-07-19 12:00:00' AS created_at
UNION ALL
SELECT '1114019557538990' AS tracking_number, '1 Gallon Honey Light' AS items_raw, '{"deltas":[{"variant":"light","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Justin Don Ramos' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1114019557538990' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-19' AS ship_date, '2026-07-19 12:00:00' AS created_at
UNION ALL
SELECT '1114105520571730' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Ruby' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1114105520571730' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-19' AS ship_date, '2026-07-19 12:00:00' AS created_at
UNION ALL
SELECT '1114215128697240' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Blesila Enriquez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1114215128697240' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-21' AS ship_date, '2026-07-21 12:00:00' AS created_at
UNION ALL
SELECT '1105534634898340' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'cristina malgapo joson' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1105534634898340' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-21' AS ship_date, '2026-07-21 12:00:00' AS created_at
UNION ALL
SELECT '1105554406605440' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Almario sanchez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1105554406605440' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-21' AS ship_date, '2026-07-21 12:00:00' AS created_at
UNION ALL
SELECT '1105926630452890' AS tracking_number, '1 Paragis Tea ; 1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'marichu frane' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1105926630452890' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-23' AS ship_date, '2026-07-23 12:00:00' AS created_at
UNION ALL
SELECT '1106055839532100' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Mary Ann yangco' AS recipient_name, 'J&T Express' AS courier, '1106055839532100' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-24' AS ship_date, '2026-07-24 12:00:00' AS created_at
UNION ALL
SELECT '1106070238096260' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Eugenio Rimando' AS recipient_name, 'J&T Express' AS courier, '1106070238096260' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-24' AS ship_date, '2026-07-24 12:00:00' AS created_at
UNION ALL
SELECT '1114866799816330' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'AlmaGrace bagsic' AS recipient_name, 'J&T Express' AS courier, '1114866799816330' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-24' AS ship_date, '2026-07-24 12:00:00' AS created_at
UNION ALL
SELECT '1106373872000028' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Richelle Manalansan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1106373872000028' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-07-25' AS ship_date, '2026-07-25 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

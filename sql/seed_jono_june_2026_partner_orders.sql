-- jono partner seller - June 2026 historical partner orders
-- 74 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-06-01 .. 2026-06-30
-- Run on production: mysql -u USER -p rts_system < sql/seed_jono_june_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'jono'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('1094818459140174', '1103610583902125', '1103668385387849', '1095405429520177', '1095405429520177D2', '1104065595216449', '1104191151798255', '1095628483360699', '1104421124856516', '1095752670147674', '1095898021320342', '1095865494556661', '1104643338348752', '1095628483360699D2', '1104421124856516D2', '1095752670147674D2', '1096500481904904', '1096910478364954', '1097121079097225', '1106636725081583', '1106740775100083', '1098227894936058', '1098397039178332', '1098401277714280', '1098428883217977', '1107093726924751', '1107122973552522', '1107172797596951', '1098583893492544', '1107295313812014', '1098670495335143', '1107311589235143', '1098781288379482', '1107516957056495', '1098975889620384', '1099002482938248', '1107700356074617', '1107765368364384', '1099114255533675', '1099134636904918', '1107964907834602', '1107963350172801', '1099655049667410', '1099718027883090', '1099924477396157', '1108606515185021', '1108607711302627', '1108630131256124', '1099973050776713', '1108655966020996', '1100009892620399', '1108807736933110', '1100190605792800', '1100206090768667', '1108915904234569', '1100398854286730', '1100510614824008', '1109189339336308', '1109393506798120', '1109430138560490', '1100837417046120', '1109536535292800', '1100868853192800', '1109605508390660', '1109676928926740', '1109761909140650', '1101292464389240', '1109971968712140', '1101303255302620', '1101342098992690', '1110025370934560', '1101360220832100', '1101406412644700', '1101450047918970');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '1094818459140174' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Filemon Lorenzo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094818459140174' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-01' AS ship_date, '2026-06-01 12:00:00' AS created_at
UNION ALL
SELECT '1103610583902125' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Maria Cristina Tabuloc' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103610583902125' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-01' AS ship_date, '2026-06-01 12:00:00' AS created_at
UNION ALL
SELECT '1103668385387849' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'valeriano bulandos' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103668385387849' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-01' AS ship_date, '2026-06-01 12:00:00' AS created_at
UNION ALL
SELECT '1095405429520177' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'dexter guillermo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1095405429520177' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-03' AS ship_date, '2026-06-03 12:00:00' AS created_at
UNION ALL
SELECT '1095405429520177D2' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'dexter guillermo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1095405429520177' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-03' AS ship_date, '2026-06-03 12:00:00' AS created_at
UNION ALL
SELECT '1104065595216449' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'robin' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104065595216449' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-03' AS ship_date, '2026-06-03 12:00:00' AS created_at
UNION ALL
SELECT '1104191151798255' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Alfredo L. David' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104191151798255' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-03' AS ship_date, '2026-06-03 12:00:00' AS created_at
UNION ALL
SELECT '1095628483360699' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'eric palacio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095628483360699' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-04' AS ship_date, '2026-06-04 12:00:00' AS created_at
UNION ALL
SELECT '1104421124856516' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Tony de la Cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104421124856516' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-04' AS ship_date, '2026-06-04 12:00:00' AS created_at
UNION ALL
SELECT '1095752670147674' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'cherry Ann dy' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095752670147674' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-04' AS ship_date, '2026-06-04 12:00:00' AS created_at
UNION ALL
SELECT '1095898021320342' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jesus William D. Que' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095898021320342' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1095865494556661' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Roberto S. De Leon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095865494556661' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1104643338348752' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'susana c Guinto' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104643338348752' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1095628483360699D2' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'eric palacio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095628483360699' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1104421124856516D2' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Tony de la Cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1104421124856516' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1095752670147674D2' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'cherry Ann dy' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1095752670147674' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-05' AS ship_date, '2026-06-05 12:00:00' AS created_at
UNION ALL
SELECT '1096500481904904' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Daisy Rollon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1096500481904904' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-09' AS ship_date, '2026-06-09 12:00:00' AS created_at
UNION ALL
SELECT '1096910478364954' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gigi Gutierrez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1096910478364954' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-09' AS ship_date, '2026-06-09 12:00:00' AS created_at
UNION ALL
SELECT '1097121079097225' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'thomas v. talmadge' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097121079097225' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-09' AS ship_date, '2026-06-09 12:00:00' AS created_at
UNION ALL
SELECT '1106636725081583' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Hector Raynal' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1106636725081583' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-13' AS ship_date, '2026-06-13 12:00:00' AS created_at
UNION ALL
SELECT '1106740775100083' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'AAA******' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1106740775100083' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-13' AS ship_date, '2026-06-13 12:00:00' AS created_at
UNION ALL
SELECT '1098227894936058' AS tracking_number, '2 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Joji Santos' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098227894936058' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-13' AS ship_date, '2026-06-13 12:00:00' AS created_at
UNION ALL
SELECT '1098397039178332' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Cornelia M. Lorenzo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098397039178332' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1098401277714280' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'maria luisa abobon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098401277714280' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1098428883217977' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'marilene abias' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098428883217977' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1107093726924751' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'eden pallen' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107093726924751' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1107122973552522' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Melba Peralta' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107122973552522' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1107172797596951' AS tracking_number, '1 Gallon Honey Dark' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'maris' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107172797596951' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1098583893492544' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Conrado Pangan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098583893492544' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1107295313812014' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'gelyn camarino' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107295313812014' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1098670495335143' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rosemarie Jimenez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098670495335143' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1107311589235143' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rosemarie Jimenez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107311589235143' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-16' AS ship_date, '2026-06-16 12:00:00' AS created_at
UNION ALL
SELECT '1098781288379482' AS tracking_number, '2 Liter Honey (1DARK, 1AMBER)' AS items_raw, NULL AS items_parsed_json, 'Emmanuel Ursais' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098781288379482' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-17' AS ship_date, '2026-06-17 12:00:00' AS created_at
UNION ALL
SELECT '1107516957056495' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Lorna d. Ylagan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107516957056495' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-17' AS ship_date, '2026-06-17 12:00:00' AS created_at
UNION ALL
SELECT '1098975889620384' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'mary anne cuartero' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098975889620384' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1099002482938248' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Bryan Flores' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099002482938248' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1107700356074617' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Trinidad bonaobra' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107700356074617' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1107765368364384' AS tracking_number, '2 Liter Honey 1DARK,  1AMBER' AS items_raw, NULL AS items_parsed_json, 'Zenaida T Ruiz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107765368364384' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1099114255533675' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rocel Quiamco' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099114255533675' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1099134636904918' AS tracking_number, '2 350mL Honey 1LIGHT, 1AMBER' AS items_raw, NULL AS items_parsed_json, 'Danny Dastas' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099134636904918' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-18' AS ship_date, '2026-06-18 12:00:00' AS created_at
UNION ALL
SELECT '1107964907834602' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Celeste Constantino' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107964907834602' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-19' AS ship_date, '2026-06-19 12:00:00' AS created_at
UNION ALL
SELECT '1107963350172801' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Russel John Pedregosa' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1107963350172801' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-19' AS ship_date, '2026-06-19 12:00:00' AS created_at
UNION ALL
SELECT '1099655049667410' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Mark Anthony Llusala' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099655049667410' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-22' AS ship_date, '2026-06-22 12:00:00' AS created_at
UNION ALL
SELECT '1099718027883090' AS tracking_number, '5 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":5,"raw":"5 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'jedecris Flores' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099718027883090' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-22' AS ship_date, '2026-06-22 12:00:00' AS created_at
UNION ALL
SELECT '1099924477396157' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'LETICIA PLASABAS' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099924477396157' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1108606515185021' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Vicente Basmayor' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1108606515185021' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1108607711302627' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'rosalinda lubbui' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1108607711302627' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1108630131256124' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Maria Cristina Somoza' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1108630131256124' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1099973050776713' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Tsuneo Akayama' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099973050776713' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1108655966020996' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Roy P. Dela Rosa' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1108655966020996' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1100009892620399' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Joey Moreno' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100009892620399' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-23' AS ship_date, '2026-06-23 12:00:00' AS created_at
UNION ALL
SELECT '1108807736933110' AS tracking_number, '3 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":3,"raw":"3 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Ayi serrano' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1108807736933110' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-24' AS ship_date, '2026-06-24 12:00:00' AS created_at
UNION ALL
SELECT '1100190605792800' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'James Tan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100190605792800' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-24' AS ship_date, '2026-06-24 12:00:00' AS created_at
UNION ALL
SELECT '1100206090768667' AS tracking_number, '2 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Ernesto c manila' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100206090768667' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-24' AS ship_date, '2026-06-24 12:00:00' AS created_at
UNION ALL
SELECT '1108915904234569' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'RANDOLPH ORTIZ' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1108915904234569' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-24' AS ship_date, '2026-06-24 12:00:00' AS created_at
UNION ALL
SELECT '1100398854286730' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Dolly Ores' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100398854286730' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-25' AS ship_date, '2026-06-25 12:00:00' AS created_at
UNION ALL
SELECT '1100510614824008' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Joneil Nutas' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100510614824008' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-26' AS ship_date, '2026-06-26 12:00:00' AS created_at
UNION ALL
SELECT '1109189339336308' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'joselito ciruela' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109189339336308' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-26' AS ship_date, '2026-06-26 12:00:00' AS created_at
UNION ALL
SELECT '1109393506798120' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Mace Buen' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109393506798120' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1109430138560490' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Leony Osuna' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109430138560490' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1100837417046120' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'CINDY AURE' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100837417046120' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1109536535292800' AS tracking_number, '1 Gallon Honey Light' AS items_raw, '{"deltas":[{"variant":"light","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'James Tan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109536535292800' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1100868853192800' AS tracking_number, '1 Gallon Honey Light' AS items_raw, '{"deltas":[{"variant":"light","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'James Tan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100868853192800' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1109605508390660' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Dennis ang' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109605508390660' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-27' AS ship_date, '2026-06-27 12:00:00' AS created_at
UNION ALL
SELECT '1109676928926740' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'John Paul Baylon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109676928926740' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-29' AS ship_date, '2026-06-29 12:00:00' AS created_at
UNION ALL
SELECT '1109761909140650' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gloria Alinea' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109761909140650' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-29' AS ship_date, '2026-06-29 12:00:00' AS created_at
UNION ALL
SELECT '1101292464389240' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Johnro Herbas' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101292464389240' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1109971968712140' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'mario p mabazza' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1109971968712140' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1101303255302620' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'carlo jay carisiosa' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101303255302620' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1101342098992690' AS tracking_number, '1 Liter Honey DARK ; 1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Glicerio Tamayo Valdez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101342098992690' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1110025370934560' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'RANDOLPH ORTIZ' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1110025370934560' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1101360220832100' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Mary Ann yangco' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101360220832100' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1101406412644700' AS tracking_number, '1 Gallon Honey Dark' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Emmanuel Z. Dela Cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101406412644700' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
UNION ALL
SELECT '1101450047918970' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'josefina lamberte jalapa' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101450047918970' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-06-30' AS ship_date, '2026-06-30 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

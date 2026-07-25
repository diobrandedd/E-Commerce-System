-- jono partner seller - May 2026 historical partner orders
-- 102 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-05-01 .. 2026-05-31
-- Run on production: mysql -u USER -p rts_system < sql/seed_jono_may_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'jono'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('1097066962803306', '1097265553400343', '1088699644832268', '1097296790359239', '1097336506924014', '1088743479483006', '1097540181445380', '1097691545762173', '1097822764588093', '1097929396740929', '1097961109120304', '1097951973780521', '1089365489636465', '1089413638180652', '1098031905228310', '1089448271837064', '1089549802978492', '1098151373197097', '1089555073348091', '1089750803572780', '1089885658336186', '1089921038012708', '1089932611064263', '1089958465702066', '1090014408910811', '1090042293857290', '1098719912973093', '1090126843063728', '1098917702825813', '1098949546354735', '1090352068592119', '1098989385641496', '1090411044218750', '1099282541794139', '1099333377776643', '1099361162364729', '1090776829547777', '1099395350358481', '1099398941230197', '1090797816072406', '1090988457296951', '1099652799900550', '1099662781520177', '1099395350358481D2', '1090797816072406D2', '1099508328268904', '1091444609364901', '1091574251435987', '1100338310763125', '1100353995404777', '1100358193044794', '1100366351028496', '1100386987804356', '1100471761003392', '1091935698108444', '1091935698108444D2', '1100706766247992', '1092133067884875', '1092293240208244', '1101040122688983', '1092448234476180', '1092482864059904', '1092496447361611', '1092527888657482', '1101249151065127', '1101312320263879', '1101330710513221', '1101365784325134', '1092806830736226', '1092819454462173', '1092831687768763', '1092920044304907', '1101598332451285', '1092944497912314', '1101673900630590', '1101763116252915', '1101770725893995', '1093148603836016', '1101809123965420', '1093352809092826', '1093374060147362', '1093411611665127', '1093506665940084', '1102198157896156', '1093582446465441', '1102235303732244', '1093629416072792', '1093704659402880', '1102431557458215', '1093967042816160', '1102785747857839', '1102854128886562', '1102842993352195', '1094294079851285', '820145046679', '820145046678', '820145046626', '820145264678', '820145264600', '820145046679D2', '820145046678D2', '820145046626D2');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '1097066962803306' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gilbert M. Leoncio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097066962803306' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-01' AS ship_date, '2026-05-01 12:00:00' AS created_at
UNION ALL
SELECT '1097265553400343' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gloria Binuya' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097265553400343' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1088699644832268' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Christian Munoz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1088699644832268' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1097296790359239' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'MONROE MAGNO' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097296790359239' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1097336506924014' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Annabel bonzon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097336506924014' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1088743479483006' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Abel G. Gernale' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1088743479483006' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1097540181445380' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Garcel Garay' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1097540181445380' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-02' AS ship_date, '2026-05-02 12:00:00' AS created_at
UNION ALL
SELECT '1097691545762173' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Kelvin Clark' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097691545762173' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-04' AS ship_date, '2026-05-04 12:00:00' AS created_at
UNION ALL
SELECT '1097822764588093' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Haide Cayago' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097822764588093' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-04' AS ship_date, '2026-05-04 12:00:00' AS created_at
UNION ALL
SELECT '1097929396740929' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Angeline leido' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097929396740929' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1097961109120304' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rey montalbo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097961109120304' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1097951973780521' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'ISABELITA TANAEL' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1097951973780521' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089365489636465' AS tracking_number, '1  Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1  Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Cici hanhardt' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089365489636465' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089413638180652' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'ernesto aguilar' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089413638180652' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1098031905228310' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'peregrino arcangel jr.' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098031905228310' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089448271837064' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ian Tan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089448271837064' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089549802978492' AS tracking_number, '3 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Dave villarosa' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089549802978492' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1098151373197097' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Elnora Marcelo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098151373197097' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089555073348091' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'zenaida sepnio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089555073348091' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-05' AS ship_date, '2026-05-05 12:00:00' AS created_at
UNION ALL
SELECT '1089750803572780' AS tracking_number, '1 Gallon Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Manuel m gonzalez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089750803572780' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-06' AS ship_date, '2026-05-06 12:00:00' AS created_at
UNION ALL
SELECT '1089885658336186' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Beng Macawile' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089885658336186' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1089921038012708' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Frances Robles' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089921038012708' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1089932611064263' AS tracking_number, '2 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Mario A. Din' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089932611064263' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1089958465702066' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'jovy morente' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1089958465702066' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1090014408910811' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Oscar Santos Lazo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090014408910811' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1090042293857290' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Ramil Gabriel' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090042293857290' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-07' AS ship_date, '2026-05-07 12:00:00' AS created_at
UNION ALL
SELECT '1098719912973093' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Melvin Penn Cunanan' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1098719912973093' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-08' AS ship_date, '2026-05-08 12:00:00' AS created_at
UNION ALL
SELECT '1090126843063728' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rodalino sacramed' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090126843063728' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-08' AS ship_date, '2026-05-08 12:00:00' AS created_at
UNION ALL
SELECT '1098917702825813' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Raul Jabol' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098917702825813' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-08' AS ship_date, '2026-05-08 12:00:00' AS created_at
UNION ALL
SELECT '1098949546354735' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Jeanit F. Junio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098949546354735' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-09' AS ship_date, '2026-05-09 12:00:00' AS created_at
UNION ALL
SELECT '1090352068592119' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'rechilda aragon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090352068592119' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-09' AS ship_date, '2026-05-09 12:00:00' AS created_at
UNION ALL
SELECT '1098989385641496' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Mario S. Soltes' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1098989385641496' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-09' AS ship_date, '2026-05-09 12:00:00' AS created_at
UNION ALL
SELECT '1090411044218750' AS tracking_number, '1 Gallon Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Chard Totten' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090411044218750' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-09' AS ship_date, '2026-05-09 12:00:00' AS created_at
UNION ALL
SELECT '1099282541794139' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Maricar Francisco' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099282541794139' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1099333377776643' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Natsu Rheign' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099333377776643' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1099361162364729' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'PATRICIO C. ALCOVER' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1099361162364729' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1090776829547777' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Rhea L. Sasondoncillo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1090776829547777' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1099395350358481' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Dan Earl A. Lomadilla' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099395350358481' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1099398941230197' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Fernando Jr Amoroso' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099398941230197' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1090797816072406' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ronald Degala' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090797816072406' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-11' AS ship_date, '2026-05-11 12:00:00' AS created_at
UNION ALL
SELECT '1090988457296951' AS tracking_number, '1 Gallon Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'maris' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090988457296951' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-12' AS ship_date, '2026-05-12 12:00:00' AS created_at
UNION ALL
SELECT '1099652799900550' AS tracking_number, '1 Gallon Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Aubrey Weston' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099652799900550' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-12' AS ship_date, '2026-05-12 12:00:00' AS created_at
UNION ALL
SELECT '1099662781520177' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'dexter guillermo' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1099662781520177' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-12' AS ship_date, '2026-05-12 12:00:00' AS created_at
UNION ALL
SELECT '1099395350358481D2' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Dan Earl A. Lomadilla' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099395350358481' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-13' AS ship_date, '2026-05-13 12:00:00' AS created_at
UNION ALL
SELECT '1090797816072406D2' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Ronald Degala' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1090797816072406' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-13' AS ship_date, '2026-05-13 12:00:00' AS created_at
UNION ALL
SELECT '1099508328268904' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Shennel De Jesus' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1099508328268904' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-13' AS ship_date, '2026-05-13 12:00:00' AS created_at
UNION ALL
SELECT '1091444609364901' AS tracking_number, '2 Liter Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'stephan luethi' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1091444609364901' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-14' AS ship_date, '2026-05-14 12:00:00' AS created_at
UNION ALL
SELECT '1091574251435987' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Edna Morit' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1091574251435987' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-15' AS ship_date, '2026-05-15 12:00:00' AS created_at
UNION ALL
SELECT '1100338310763125' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jose Alejandro Agalabia' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100338310763125' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1100353995404777' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Richie Corre' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1100353995404777' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1100358193044794' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Gerhard Gauss' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1100358193044794' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1100366351028496' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Orlando yecyecan' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100366351028496' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1100386987804356' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Elmer Talavera Rait' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100386987804356' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1100471761003392' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'caterine metante' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100471761003392' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1091935698108444' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Cristine Marie Idio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1091935698108444' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-16' AS ship_date, '2026-05-16 12:00:00' AS created_at
UNION ALL
SELECT '1091935698108444D2' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Cristine Marie Idio' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1091935698108444' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-18' AS ship_date, '2026-05-18 12:00:00' AS created_at
UNION ALL
SELECT '1100706766247992' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Andrew Jimenez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1100706766247992' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-18' AS ship_date, '2026-05-18 12:00:00' AS created_at
UNION ALL
SELECT '1092133067884875' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Norberto Tolentino' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092133067884875' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-18' AS ship_date, '2026-05-18 12:00:00' AS created_at
UNION ALL
SELECT '1092293240208244' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Manuel Baltazar' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092293240208244' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-18' AS ship_date, '2026-05-18 12:00:00' AS created_at
UNION ALL
SELECT '1101040122688983' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'BENERANDO I. CABILLON' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101040122688983' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-19' AS ship_date, '2026-05-19 12:00:00' AS created_at
UNION ALL
SELECT '1092448234476180' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'orly catud' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1092448234476180' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-19' AS ship_date, '2026-05-19 12:00:00' AS created_at
UNION ALL
SELECT '1092482864059904' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'madjelyn malintad talledo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092482864059904' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-19' AS ship_date, '2026-05-19 12:00:00' AS created_at
UNION ALL
SELECT '1092496447361611' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Kevin Manarang' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092496447361611' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-19' AS ship_date, '2026-05-19 12:00:00' AS created_at
UNION ALL
SELECT '1092527888657482' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'marval fernando' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092527888657482' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-19' AS ship_date, '2026-05-19 12:00:00' AS created_at
UNION ALL
SELECT '1101249151065127' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'arnel labbao' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101249151065127' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-20' AS ship_date, '2026-05-20 12:00:00' AS created_at
UNION ALL
SELECT '1101312320263879' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Josefina Du' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101312320263879' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-20' AS ship_date, '2026-05-20 12:00:00' AS created_at
UNION ALL
SELECT '1101330710513221' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Alexander caponpon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101330710513221' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-20' AS ship_date, '2026-05-20 12:00:00' AS created_at
UNION ALL
SELECT '1101365784325134' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Florence A. Llamera' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101365784325134' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-20' AS ship_date, '2026-05-20 12:00:00' AS created_at
UNION ALL
SELECT '1092806830736226' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Liezl Corcuera' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092806830736226' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1092819454462173' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Kelvin Clark' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092819454462173' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1092831687768763' AS tracking_number, '3 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":3,"raw":"3 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Lorena Beliran' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1092831687768763' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1092920044304907' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'Dorotea Flores' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092920044304907' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1101598332451285' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Sheila Marie Ombrog' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101598332451285' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1092944497912314' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Lolita Davis' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1092944497912314' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-21' AS ship_date, '2026-05-21 12:00:00' AS created_at
UNION ALL
SELECT '1101673900630590' AS tracking_number, '1 Gallon Honey Amber' AS items_raw, '{"deltas":[{"variant":"amber","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Felipe S Bina' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101673900630590' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-22' AS ship_date, '2026-05-22 12:00:00' AS created_at
UNION ALL
SELECT '1101763116252915' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Brenda Yap' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101763116252915' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-22' AS ship_date, '2026-05-22 12:00:00' AS created_at
UNION ALL
SELECT '1101770725893995' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Teodoro Eric D. Valena Jr' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101770725893995' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-22' AS ship_date, '2026-05-22 12:00:00' AS created_at
UNION ALL
SELECT '1093148603836016' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'aira' AS recipient_name, 'Lazada-PH-Flash Express PH' AS courier, '1093148603836016' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-22' AS ship_date, '2026-05-22 12:00:00' AS created_at
UNION ALL
SELECT '1101809123965420' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Mary Jane Agulto' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1101809123965420' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-22' AS ship_date, '2026-05-22 12:00:00' AS created_at
UNION ALL
SELECT '1093352809092826' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Alvin S. Alvarez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093352809092826' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-23' AS ship_date, '2026-05-23 12:00:00' AS created_at
UNION ALL
SELECT '1093374060147362' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Jacqueline J. Perez' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093374060147362' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-23' AS ship_date, '2026-05-23 12:00:00' AS created_at
UNION ALL
SELECT '1093411611665127' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'arnel labbao' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093411611665127' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-23' AS ship_date, '2026-05-23 12:00:00' AS created_at
UNION ALL
SELECT '1093506665940084' AS tracking_number, '1 Gallon Honey Light' AS items_raw, '{"deltas":[{"variant":"light","size":"gallon","qty":1}],"other_items":[]}' AS items_parsed_json, 'Mary Grace Novida' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093506665940084' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-25' AS ship_date, '2026-05-25 12:00:00' AS created_at
UNION ALL
SELECT '1102198157896156' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Pedro c Balisi Jr' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102198157896156' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-25' AS ship_date, '2026-05-25 12:00:00' AS created_at
UNION ALL
SELECT '1093582446465441' AS tracking_number, '2 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Garry Uy' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093582446465441' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-25' AS ship_date, '2026-05-25 12:00:00' AS created_at
UNION ALL
SELECT '1102235303732244' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'Myla Cherry' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102235303732244' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-25' AS ship_date, '2026-05-25 12:00:00' AS created_at
UNION ALL
SELECT '1093629416072792' AS tracking_number, '2 Liter Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'Eveline Jover' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093629416072792' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-25' AS ship_date, '2026-05-25 12:00:00' AS created_at
UNION ALL
SELECT '1093704659402880' AS tracking_number, '1 250mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'Jefferson Anay' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093704659402880' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-26' AS ship_date, '2026-05-26 12:00:00' AS created_at
UNION ALL
SELECT '1102431557458215' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Lianne Tuazon' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102431557458215' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-26' AS ship_date, '2026-05-26 12:00:00' AS created_at
UNION ALL
SELECT '1093967042816160' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Nilo Concepcion' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1093967042816160' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-27' AS ship_date, '2026-05-27 12:00:00' AS created_at
UNION ALL
SELECT '1102785747857839' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Marvin Villanueva Madara' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102785747857839' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-27' AS ship_date, '2026-05-27 12:00:00' AS created_at
UNION ALL
SELECT '1102854128886562' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'joel foncardas' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102854128886562' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-28' AS ship_date, '2026-05-28 12:00:00' AS created_at
UNION ALL
SELECT '1102842993352195' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Nicolas Garcia' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1102842993352195' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-28' AS ship_date, '2026-05-28 12:00:00' AS created_at
UNION ALL
SELECT '1094294079851285' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Sheila Marie Ombrog' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094294079851285' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-28' AS ship_date, '2026-05-28 12:00:00' AS created_at
UNION ALL
SELECT '820145046679' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'ernilo p.dela cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094403208908800' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-29' AS ship_date, '2026-05-29 12:00:00' AS created_at
UNION ALL
SELECT '820145046678' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'arnel bigalbal' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094409294931983' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-29' AS ship_date, '2026-05-29 12:00:00' AS created_at
UNION ALL
SELECT '820145046626' AS tracking_number, '2 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Imelda Asilo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103107147644335' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-29' AS ship_date, '2026-05-29 12:00:00' AS created_at
UNION ALL
SELECT '820145264678' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'LINDA GONZALES' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103240768768185' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-30' AS ship_date, '2026-05-30 12:00:00' AS created_at
UNION ALL
SELECT '820145264600' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'Demetrio D. Hugo jr' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094674669488675' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-30' AS ship_date, '2026-05-30 12:00:00' AS created_at
UNION ALL
SELECT '820145046679D2' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'ernilo p.dela cruz' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094403208908800' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-30' AS ship_date, '2026-05-30 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '820145046678D2' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'arnel bigalbal' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1094409294931983' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-30' AS ship_date, '2026-05-30 12:00:00' AS created_at
UNION ALL
SELECT '820145046626D2' AS tracking_number, '2 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'Imelda Asilo' AS recipient_name, 'Lazada-PH-J&T Express PH' AS courier, '1103107147644335' AS order_no, 'partner' AS source_pipeline, 'jono' AS partner_seller, 'shipped' AS status, '2026-05-30' AS ship_date, '2026-05-30 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

-- joven partner seller - March 2026 historical partner orders
-- 38 shipped parcels - ship_date = section Pickup Date
-- Ship date range in file: 2026-03-01 .. 2026-03-31
-- Run on production: mysql -u USER -p rts_system < sql/seed_joven_march_2026_partner_orders.sql
--
-- Safe re-run: deletes this batch by tracking_number, then inserts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM online_order_parcels
WHERE partner_seller = 'joven'
  AND status = 'shipped'
  AND csr_batch_id IS NULL
  AND tracking_number IN ('582915844735731121', '582915924885014257', '582917246777460145', '582917981779297861', '582932369744365039', '582940951262955238', '582941371279443837', '582959396293674350', '583004284372944235', '583024735063475459', '583016000000000000', '583028382803461270', '583060589810779696', '583070375427933972', '583086682487228307', '583093274795018204', '583096836323182483', '583102277855381337', '583129512260372360', '583138011630044397', '583142461661742119', '583158718016358146', '583215738744177716', '583221797098980917', '583237283728951018', '583266011389133950', '583266556619687517', '583268157953049758', '583278933823096000', '583284425586148000', '583290607538177000', '583288552370308000', '583297032101397575', '583297032119617231', '583299164827518665', '583300639147263095', '583304878756562460', '583305332147979300');

INSERT INTO online_order_parcels (
  tracking_number, items_raw, items_parsed_json, recipient_name, courier, order_no,
  source_pipeline, partner_seller, status, ship_date, created_at
)
SELECT * FROM (
SELECT '582915844735731121' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'M***elline P* u***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582915844735731121' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582915924885014257' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'w*** k***na' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582915924885014257' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582917246777460145' AS tracking_number, '1 Cacao Tablea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Jar Cacao","qty":1,"raw":"1 Cacao Tablea","product_key":"jar_cacao"}]}' AS items_parsed_json, 'M***elline P* u***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582917246777460145' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582917981779297861' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'A***eda E***io T*' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582917981779297861' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582932369744365039' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'K***erine A***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582932369744365039' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582940951262955238' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'T* Y***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582940951262955238' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582941371279443837' AS tracking_number, '1 350mL Honey DARK' AS items_raw, NULL AS items_parsed_json, 'D*** G** G***oa' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582941371279443837' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-06' AS ship_date, '2026-03-06 12:00:00' AS created_at
UNION ALL
SELECT '582959396293674350' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'M***e' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '582959396293674350' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-09' AS ship_date, '2026-03-09 12:00:00' AS created_at
UNION ALL
SELECT '583004284372944235' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'B*** M***een U***se' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583004284372944235' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-10' AS ship_date, '2026-03-10 12:00:00' AS created_at
UNION ALL
SELECT '583024735063475459' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'J***any C***san M***tares' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583024735063475459' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-11' AS ship_date, '2026-03-11 12:00:00' AS created_at
UNION ALL
SELECT '583016000000000000' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'B***et M***uita' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583016000000000000' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-11' AS ship_date, '2026-03-11 12:00:00' AS created_at
UNION ALL
SELECT '583028382803461270' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'M* K**' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583028382803461270' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-12' AS ship_date, '2026-03-12 12:00:00' AS created_at
UNION ALL
SELECT '583060589810779696' AS tracking_number, '3 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":3,"raw":"3 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'K***ty M** U S***g' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583060589810779696' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-14' AS ship_date, '2026-03-14 12:00:00' AS created_at
UNION ALL
SELECT '583070375427933972' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'j*** s***c' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583070375427933972' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-14' AS ship_date, '2026-03-14 12:00:00' AS created_at
UNION ALL
SELECT '583086682487228307' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'G*** P***ugal' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583086682487228307' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-16' AS ship_date, '2026-03-16 12:00:00' AS created_at
UNION ALL
SELECT '583093274795018204' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'C***y B***in' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583093274795018204' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-16' AS ship_date, '2026-03-16 12:00:00' AS created_at
UNION ALL
SELECT '583096836323182483' AS tracking_number, '2 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":2,"raw":"2 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'B***y V***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583096836323182483' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-16' AS ship_date, '2026-03-16 12:00:00' AS created_at
UNION ALL
SELECT '583102277855381337' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'H***l V***anueva' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583102277855381337' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-16' AS ship_date, '2026-03-16 12:00:00' AS created_at
UNION ALL
SELECT '583129512260372360' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'S***win E***n T* P**' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583129512260372360' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-18' AS ship_date, '2026-03-18 12:00:00' AS created_at
UNION ALL
SELECT '583138011630044397' AS tracking_number, '2 Liter Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'R** O***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583138011630044397' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-19' AS ship_date, '2026-03-19 12:00:00' AS created_at
UNION ALL
SELECT '583142461661742119' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'M***ISA P* A***ZA' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583142461661742119' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-19' AS ship_date, '2026-03-19 12:00:00' AS created_at
UNION ALL
SELECT '583158718016358146' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'A***l P***iregan' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583158718016358146' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-21' AS ship_date, '2026-03-21 12:00:00' AS created_at
UNION ALL
SELECT '583215738744177716' AS tracking_number, '1 Paragis Tea' AS items_raw, '{"deltas":[],"other_items":[{"label":"Paragis Tea","qty":1,"raw":"1 Paragis Tea","product_key":"pt"}]}' AS items_parsed_json, 'E*** M** S***man' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583215738744177716' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-25' AS ship_date, '2026-03-25 12:00:00' AS created_at
UNION ALL
SELECT '583221797098980917' AS tracking_number, '2 Liter Honey AMBER and DARK' AS items_raw, NULL AS items_parsed_json, 'P***lip' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583221797098980917' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-25' AS ship_date, '2026-03-25 12:00:00' AS created_at
UNION ALL
SELECT '583237283728951018' AS tracking_number, '2 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'J***lou A***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583237283728951018' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-26' AS ship_date, '2026-03-26 12:00:00' AS created_at
UNION ALL
SELECT '583266011389133950' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'E***uiel G***ntiza' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583266011389133950' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '583266556619687517' AS tracking_number, '3 Liter Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'y***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583266556619687517' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '583268157953049758' AS tracking_number, '3 Liter Honey LAD' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":3},{"variant":"amber","size":"liter","qty":3},{"variant":"dark","size":"liter","qty":3}],"other_items":[]}' AS items_parsed_json, 'T***s n***g' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583268157953049758' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-28' AS ship_date, '2026-03-28 12:00:00' AS created_at
UNION ALL
SELECT '583278933823096000' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'M* A***la S***pa' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583278933823096000' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '583284425586148000' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'A** R***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583284425586148000' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '583290607538177000' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'A***L P***UA' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583290607538177000' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '583288552370308000' AS tracking_number, '1 Liter Honey LIGHT' AS items_raw, '{"deltas":[{"variant":"light","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'N***ta S***ano' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583288552370308000' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-30' AS ship_date, '2026-03-30 12:00:00' AS created_at
UNION ALL
SELECT '583297032101397575' AS tracking_number, '1 350mL Honey LIGHT' AS items_raw, NULL AS items_parsed_json, 'r***a' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583297032101397575' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '583297032119617231' AS tracking_number, '1 350mL Honey AMBER' AS items_raw, NULL AS items_parsed_json, 'W***g A***ada' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583297032119617231' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '583299164827518665' AS tracking_number, '2 Liter Honey DARK' AS items_raw, NULL AS items_parsed_json, 'T***rau P***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583299164827518665' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '583300639147263095' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'l***lhyn r***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583300639147263095' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '583304878756562460' AS tracking_number, '1 Liter Honey DARK' AS items_raw, '{"deltas":[{"variant":"dark","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'I*** R*** M M***z' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583304878756562460' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
UNION ALL
SELECT '583305332147979300' AS tracking_number, '1 Liter Honey AMBER' AS items_raw, '{"deltas":[{"variant":"amber","size":"liter","qty":1}],"other_items":[]}' AS items_parsed_json, 'J***lito d* l** A***' AS recipient_name, 'TikTok-PH-J&T Express' AS courier, '583305332147979300' AS order_no, 'tiktok' AS source_pipeline, 'joven' AS partner_seller, 'shipped' AS status, '2026-03-31' AS ship_date, '2026-03-31 12:00:00' AS created_at
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM online_order_parcels p WHERE p.tracking_number = t.tracking_number
);

SET FOREIGN_KEY_CHECKS = 1;

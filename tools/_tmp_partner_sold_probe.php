<?php
require 'c:/xampp/htdocs/rts/includes/db.php';
$pdo = ims_pdo();
$samples = ['585308908123227928','585311156507280672','585823488420578361','585311869091546246','585334005342308168'];
foreach ($samples as $ono) {
  $st = $pdo->prepare('SELECT id, order_no, partner_seller, status, ship_date, tracking_number FROM online_order_parcels WHERE order_no = ?');
  $st->execute([$ono]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  echo $ono . ' => ' . ($rows ? json_encode($rows) : 'NOT FOUND') . PHP_EOL;
}
echo PHP_EOL . "--- Aug 2026 ship_date by partner/status ---\n";
$st = $pdo->query("SELECT partner_seller, status, COUNT(*) AS c FROM online_order_parcels WHERE ship_date BETWEEN '2026-08-01' AND '2026-08-31' GROUP BY partner_seller, status ORDER BY 1,2");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) echo json_encode($r).PHP_EOL;
echo PHP_EOL . "--- order_no LIKE 585% ---\n";
$st = $pdo->query("SELECT partner_seller, status, COUNT(*) AS c FROM online_order_parcels WHERE order_no LIKE '585%' GROUP BY partner_seller, status");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) echo json_encode($r).PHP_EOL;
echo PHP_EOL . "--- match rate for 188 unique from file (sample: any partner, any status) ---\n";
$ons = json_decode(file_get_contents('c:/xampp/htdocs/rts/storage/_tmp_partner_ons.json'), true);

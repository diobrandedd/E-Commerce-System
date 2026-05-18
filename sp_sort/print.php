<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sp_sort_user();
$pdo = rts_pdo();
$batchId = isset($_GET['batch']) ? (int) $_GET['batch'] : 0;
if ($batchId < 1) {
    exit('Invalid batch.');
}
$batch = sp_sort_batch_or_404($pdo, $batchId);

$printMeta = json_decode((string) ($batch['print_meta'] ?? '{}'), true);
if (!is_array($printMeta)) {
    $printMeta = sp_sort_default_print_meta();
} else {
    $printMeta = sp_sort_resolve_print_meta($printMeta);
}
$preparedBy = trim((string) ($batch['prepared_by'] ?? ''));

$loaded = sp_sort_load_batch_rows($pdo, $batchId);
$tableRows = [];
foreach ($loaded as $i => $item) {
    $norm = sp_sort_normalize_pyx_row($item['row']);
    $tableRows[] = [
        'num' => $i + 1,
        'order' => (string) ($norm[1] ?? ''),
        'name' => (string) ($norm[2] ?? ''),
        'item' => (string) ($norm[3] ?? ''),
        'courier' => (string) ($norm[4] ?? ''),
        'courier_code' => (string) ($item['meta']['courier'] ?? 'unknown'),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Print batch #<?= (int) $batchId ?></title>
  <style>
    @page { size: A4 portrait; margin: 10mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; margin: 0; color: #222; }
    .banner { margin-bottom: 6px; }
    .banner .brand { font-size: 14px; font-weight: bold; }
    .banner .dates { font-size: 12px; font-weight: bold; margin: 4px 0; }
    .banner .notes {
      background: #ff9900; padding: 4px 6px; font-weight: bold; font-size: 9px;
      margin: 3px 0; display: inline-block; max-width: 55%;
    }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #ccc; padding: 3px 4px; vertical-align: top; word-wrap: break-word; }
    th {
      background: #fff2cc; font-size: 11px; font-weight: bold; text-align: center;
    }
    col.c-no { width: 4%; }
    col.c-order { width: 22%; }
    col.c-name { width: 22%; }
    col.c-item { width: 34%; }
    col.c-courier { width: 18%; }
    td.col-item { font-weight: bold; }
    .courier-flash { background: #fff9c4 !important; }
    .courier-spx { background: #ffe0b2 !important; }
    .footer { margin-top: 18px; font-size: 11px; }
    .footer strong { margin-right: 6px; }
    .no-print { margin-bottom: 10px; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <div class="no-print">
    <?php if ($preparedBy === ''): ?>
      <p style="color:#b91c1c;">Export from the batch page with a <strong>Prepared by</strong> name first.</p>
    <?php endif; ?>
    <button type="button" onclick="window.print()">Print</button>
    <a href="workflow.php?batch=<?= (int) $batchId ?>">Back to batch</a>
  </div>

  <div class="banner">
    <div class="brand"><?= htmlspecialchars((string) $printMeta['brand'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="dates"><?= htmlspecialchars((string) $printMeta['order_date_line'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="notes"><?= htmlspecialchars((string) $printMeta['note_line_1'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="dates"><?= htmlspecialchars((string) $printMeta['pickup_date_line'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="notes"><?= htmlspecialchars((string) $printMeta['note_line_2'], ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <table>
    <colgroup>
      <col class="c-no"><col class="c-order"><col class="c-name"><col class="c-item"><col class="c-courier">
    </colgroup>
    <thead>
      <tr>
        <th></th>
        <th>Order No.</th>
        <th>Name</th>
        <th>Item/s</th>
        <th>Courier</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tableRows as $tr):
          $cc = $tr['courier_code'];
          $cls = $cc === 'flash' ? 'courier-flash' : ($cc === 'spx' ? 'courier-spx' : '');
          ?>
      <tr>
        <td><?= (int) $tr['num'] ?></td>
        <td><?= htmlspecialchars($tr['order'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($tr['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="col-item"><?= htmlspecialchars($tr['item'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="<?= htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tr['courier'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">
    <strong>Prepared By:</strong> <?= htmlspecialchars($preparedBy !== '' ? $preparedBy : '________________', ENT_QUOTES, 'UTF-8') ?>
    <span style="margin-left:2rem;"><?= htmlspecialchars(date('F j, Y'), ENT_QUOTES, 'UTF-8') ?></span>
  </div>
</body>
</html>

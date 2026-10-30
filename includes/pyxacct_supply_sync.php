<?php

declare(strict_types=1);

/**
 * Push priced supply stock-in lots into PyxAcct as Packaging / Supplies expenses.
 * Uses the same API base URL and token as honey AP sync.
 * Supply posting has its own kill switch and defaults off.
 *
 * Monthly inventory recounts, zero-priced lots, packaging lots, and count corrections are not posted.
 * Failures are logged and do not undo the stock-in.
 */

require_once __DIR__ . '/pyxacct_walkin_sync.php';

function pyxacct_supply_sync_enabled(PDO $rtsPdo): bool
{
    $raw = app_setting_get($rtsPdo, 'pyxacct_supply_sync_enabled', '0');

    return $raw === '1' || strtolower((string) $raw) === 'true' || $raw === 'yes';
}

function pyxacct_supply_sync_enabled_save(PDO $rtsPdo, bool $enabled, ?int $userId = null): bool
{
    app_setting_set($rtsPdo, 'pyxacct_supply_sync_enabled', $enabled ? '1' : '0', $userId);

    return $enabled;
}

/**
 * @param array{entry_id:int,stock_id:int,item_name:string,amount:float,qty:float,unit:string,entry_date:string} $lot
 */
function pyxacct_sync_supply_lot(PDO $rtsPdo, array $lot): void
{
    $entryId = (int) ($lot['entry_id'] ?? 0);
    try {
        if ($entryId < 1 || !pyxacct_supply_sync_enabled($rtsPdo)) {
            return;
        }
        $amount = round((float) ($lot['amount'] ?? 0), 2);
        if ($amount <= 0) {
            return;
        }
        $itemName = trim((string) ($lot['item_name'] ?? ''));
        if ($itemName === '') {
            $itemName = 'Supply #' . (int) ($lot['stock_id'] ?? 0);
        }
        $date = (string) ($lot['entry_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $base = pyxacct_api_base_url_get($rtsPdo);
        $token = pyxacct_api_token_get($rtsPdo);
        if ($base === '' || $token === '') {
            return;
        }

        $result = pyxacct_api_post($base, $token, 'ingest_supply', [
            'source_key' => 'rts:supply_lot:' . $entryId,
            'item_name' => $itemName,
            'amount' => $amount,
            'entry_date' => $date,
            'meta' => [
                'stock_entry_id' => $entryId,
                'stock_id' => (int) ($lot['stock_id'] ?? 0),
                'item_name' => $itemName,
                'qty' => (float) ($lot['qty'] ?? 0),
                'unit' => (string) ($lot['unit'] ?? ''),
                'lot_total' => $amount,
            ],
        ]);
        if (empty($result['ok'])) {
            error_log('pyxacct_sync_supply_lot #' . $entryId . ': ' . ($result['error'] ?? 'failed'));
        }
    } catch (Throwable $e) {
        error_log('pyxacct_sync_supply_lot #' . $entryId . ': ' . $e->getMessage());
    }
}

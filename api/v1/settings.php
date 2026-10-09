<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/pos_mobile_staff.php';

/**
 * System settings API (executive).
 *
 *   GET  ?action=pos_pins_list
 *   POST ?action=pos_pin_update   -> { id, pin, pin_confirm, label?, is_active? }
 *   POST ?action=pos_pin_create   -> { pin, pin_confirm, label? }
 *   GET  ?action=pos_mobile_staff_list
 *   GET  ?action=pos_mobile_staff_candidates
 *   POST ?action=pos_mobile_staff_assign   -> { user_id }
 *   POST ?action=pos_mobile_staff_unassign -> { user_id }
 *   GET  ?action=honey_volume_get
 *   POST ?action=honey_volume_save -> { ml_per_gallon }
 *   GET  ?action=pt_bom_get
 *   POST ?action=pt_bom_save -> { grams_per_tea_bag, tea_bags_per_pouch, bag_pool_deduct? }
 *   GET  ?action=brc_bom_get
 *   POST ?action=brc_bom_save -> { grams_per_pouch, grams_per_bag, bag_pool_deduct? }
 *   GET  ?action=jar_cacao_bom_get
 *   POST ?action=jar_cacao_bom_save -> { grams_per_jar, grams_per_bag, bag_pool_deduct? }
 *   GET  ?action=pyxacct_sync_get
 *   POST ?action=pyxacct_sync_save -> { enabled, api_base_url, api_token? }
 *   POST ?action=pyxacct_sync_test
 */

api_dispatch([
    'pos_pins_list' => 'api_settings_pos_pins_list',
    'pos_pin_update' => 'api_settings_pos_pin_update',
    'pos_pin_create' => 'api_settings_pos_pin_create',
    'pos_mobile_staff_list' => 'api_settings_pos_mobile_staff_list',
    'pos_mobile_staff_candidates' => 'api_settings_pos_mobile_staff_candidates',
    'pos_mobile_staff_assign' => 'api_settings_pos_mobile_staff_assign',
    'pos_mobile_staff_unassign' => 'api_settings_pos_mobile_staff_unassign',
    'honey_volume_get' => 'api_settings_honey_volume_get',
    'honey_volume_save' => 'api_settings_honey_volume_save',
    'pt_bom_get' => 'api_settings_pt_bom_get',
    'pt_bom_save' => 'api_settings_pt_bom_save',
    'brc_bom_get' => 'api_settings_brc_bom_get',
    'brc_bom_save' => 'api_settings_brc_bom_save',
    'jar_cacao_bom_get' => 'api_settings_jar_cacao_bom_get',
    'jar_cacao_bom_save' => 'api_settings_jar_cacao_bom_save',
    'admin_password_get' => 'api_settings_admin_password_get',
    'admin_password_save' => 'api_settings_admin_password_save',
    'pyxacct_sync_get' => 'api_settings_pyxacct_sync_get',
    'pyxacct_sync_save' => 'api_settings_pyxacct_sync_save',
    'pyxacct_sync_test' => 'api_settings_pyxacct_sync_test',
]);

function api_settings_require_executive(): void
{
    api_require_permission('settings.manage');
}

/** @param mixed $raw */
function api_settings_bool($raw, bool $default = true): bool
{
    if ($raw === null || $raw === '') {
        return $default;
    }
    if (is_bool($raw)) {
        return $raw;
    }
    if (is_int($raw) || is_float($raw)) {
        return ((int) $raw) !== 0;
    }
    $v = strtolower(trim((string) $raw));
    if (in_array($v, ['0', 'false', 'off', 'no'], true)) {
        return false;
    }
    if (in_array($v, ['1', 'true', 'on', 'yes'], true)) {
        return true;
    }

    return $default;
}

function api_settings_pos_pin_valid(string $pin): bool
{
    return (bool) preg_match('/^[0-9]{4,12}$/', $pin);
}

/** @return array{id: int, label: string, is_active: bool, created_at: string} */
function api_settings_serialize_pos_pin(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'label' => (string) $row['label'],
        'is_active' => (int) ($row['is_active'] ?? 0) === 1,
        'created_at' => (string) $row['created_at'],
    ];
}

function api_settings_pos_mobile_staff_list(): void
{
    api_method_allowed(['GET']);
    api_settings_require_executive();
    api_ok(['staff' => pos_mobile_staff_list(ims_pdo())]);
}

function api_settings_pos_mobile_staff_candidates(): void
{
    api_method_allowed(['GET']);
    api_settings_require_executive();
    api_ok(['users' => pos_mobile_staff_candidates(ims_pdo())]);
}

function api_settings_pos_mobile_staff_assign(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $exec = api_require_permission('settings.manage');
    $userId = (int) ($input['user_id'] ?? 0);
    try {
        pos_mobile_staff_assign(ims_pdo(), $userId, (int) ($exec['id'] ?? 0));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['assigned' => true, 'staff' => pos_mobile_staff_list(ims_pdo())]);
}

function api_settings_pos_mobile_staff_unassign(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    $userId = (int) ($input['user_id'] ?? 0);
    try {
        pos_mobile_staff_unassign(ims_pdo(), $userId);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['assigned' => false, 'staff' => pos_mobile_staff_list(ims_pdo())]);
}

function api_settings_pos_pins_list(): void
{
    api_method_allowed(['GET']);
    api_settings_require_executive();

    $st = ims_pdo()->query(
        'SELECT id, label, is_active, created_at FROM pos_pins ORDER BY id ASC'
    );
    $pins = array_map(
        'api_settings_serialize_pos_pin',
        $st ? $st->fetchAll(PDO::FETCH_ASSOC) : []
    );

    api_ok(['pins' => $pins]);
}

function api_settings_pos_pin_update(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();

    $id = (int) ($input['id'] ?? 0);
    $label = trim((string) ($input['label'] ?? ''));
    $pin = trim((string) ($input['pin'] ?? ''));
    $pinConfirm = trim((string) ($input['pin_confirm'] ?? ''));
    $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 ? 1 : 0) : null;

    if ($id < 1) {
        api_fail('Invalid POS terminal id.', 422);
    }
    if ($label === '') {
        api_fail('Terminal label is required.', 422);
    }

    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT id FROM pos_pins WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    if (!$st->fetch()) {
        api_fail('POS terminal not found.', 404);
    }

    $changingPin = $pin !== '' || $pinConfirm !== '';
    if ($changingPin) {
        if ($pin !== $pinConfirm) {
            api_fail('PIN and confirmation do not match.', 422);
        }
        if (!api_settings_pos_pin_valid($pin)) {
            api_fail('POS PIN must be 4-12 digits.', 422);
        }
        $hash = password_hash($pin, PASSWORD_BCRYPT);
        if ($hash === false) {
            api_fail('Could not save PIN.', 500);
        }
        $upd = $pdo->prepare('UPDATE pos_pins SET pin_hash = ?, label = ?, is_active = COALESCE(?, is_active) WHERE id = ?');
        $upd->execute([$hash, $label, $isActive, $id]);
    } else {
        $upd = $pdo->prepare('UPDATE pos_pins SET label = ?, is_active = COALESCE(?, is_active) WHERE id = ?');
        $upd->execute([$label, $isActive, $id]);
    }

    $row = $pdo->prepare('SELECT id, label, is_active, created_at FROM pos_pins WHERE id = ?');
    $row->execute([$id]);
    $saved = $row->fetch(PDO::FETCH_ASSOC);

    api_ok(['pin' => $saved ? api_settings_serialize_pos_pin($saved) : null, 'pin_changed' => $changingPin]);
}

function api_settings_pos_pin_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();

    $label = trim((string) ($input['label'] ?? 'POS Terminal'));
    $pin = trim((string) ($input['pin'] ?? ''));
    $pinConfirm = trim((string) ($input['pin_confirm'] ?? ''));

    if ($label === '') {
        $label = 'POS Terminal';
    }
    if ($pin === '') {
        api_fail('PIN is required.', 422);
    }
    if ($pin !== $pinConfirm) {
        api_fail('PIN and confirmation do not match.', 422);
    }
    if (!api_settings_pos_pin_valid($pin)) {
        api_fail('POS PIN must be 4-12 digits.', 422);
    }

    $hash = password_hash($pin, PASSWORD_BCRYPT);
    if ($hash === false) {
        api_fail('Could not save PIN.', 500);
    }

    $pdo = ims_pdo();
    $ins = $pdo->prepare('INSERT INTO pos_pins (pin_hash, label, is_active) VALUES (?, ?, 1)');
    $ins->execute([$hash, $label]);
    $id = (int) $pdo->lastInsertId();

    $st = $pdo->prepare('SELECT id, label, is_active, created_at FROM pos_pins WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    api_ok(['pin' => $row ? api_settings_serialize_pos_pin($row) : null]);
}

function api_settings_honey_volume_get(): void
{
    api_method_allowed(['GET']);
    // Readable by any IMS user so Production scan preview matches server math.
    api_require_ims_user();
    require_once dirname(__DIR__, 2) . '/includes/honey_volume.php';
    $pdo = ims_pdo();
    api_ok([
        'ml_per_gallon' => honey_ml_per_gallon($pdo),
        'default_ml_per_gallon' => HONEY_ML_PER_US_GALLON,
        'min' => HONEY_ML_PER_GALLON_MIN,
        'max' => HONEY_ML_PER_GALLON_MAX,
        'packaging_deduct' => honey_packaging_deduct_enabled($pdo),
    ]);
}

function api_settings_honey_volume_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/honey_volume.php';

    $ml = isset($input['ml_per_gallon']) ? (float) $input['ml_per_gallon'] : 0.0;
    $user = api_require_ims_user();
    $userId = (int) ($user['id'] ?? 0) ?: null;
    try {
        $saved = honey_ml_per_gallon_save(ims_pdo(), $ml, $userId);
        $packagingDeduct = honey_packaging_deduct_enabled(ims_pdo());
        if (array_key_exists('packaging_deduct', $input)) {
            $packagingDeduct = honey_packaging_deduct_save(
                ims_pdo(),
                api_settings_bool($input['packaging_deduct'], true),
                $userId
            );
        }
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }

    api_ok([
        'ml_per_gallon' => $saved,
        'default_ml_per_gallon' => HONEY_ML_PER_US_GALLON,
        'packaging_deduct' => $packagingDeduct,
    ]);
}

function api_settings_pt_bom_get(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    require_once dirname(__DIR__, 2) . '/includes/dept_pt_settings.php';
    api_ok(dept_pt_bom_snapshot(ims_pdo()));
}

function api_settings_pt_bom_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/dept_pt_settings.php';

    $user = api_require_ims_user();
    try {
        $saved = dept_pt_bom_save(
            ims_pdo(),
            (float) ($input['grams_per_tea_bag'] ?? 0),
            (float) ($input['tea_bags_per_pouch'] ?? 0),
            api_settings_bool($input['bag_pool_deduct'] ?? true, true),
            (int) ($user['id'] ?? 0) ?: null,
            isset($input['freebie_grams']) ? (float) $input['freebie_grams'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }

    api_ok(array_merge($saved, dept_pt_bom_snapshot(ims_pdo())));
}

function api_settings_brc_bom_get(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    require_once dirname(__DIR__, 2) . '/includes/dept_brc_settings.php';
    api_ok(dept_brc_bom_snapshot(ims_pdo()));
}

function api_settings_brc_bom_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/dept_brc_settings.php';

    $user = api_require_ims_user();
    try {
        $saved = dept_brc_bom_save(
            ims_pdo(),
            (float) ($input['grams_per_pouch'] ?? 0),
            (float) ($input['grams_per_bag'] ?? 0),
            api_settings_bool($input['bag_pool_deduct'] ?? true, true),
            (int) ($user['id'] ?? 0) ?: null,
            isset($input['freebie_grams']) ? (float) $input['freebie_grams'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }

    api_ok(array_merge($saved, dept_brc_bom_snapshot(ims_pdo())));
}

function api_settings_jar_cacao_bom_get(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    require_once dirname(__DIR__, 2) . '/includes/dept_jar_cacao_settings.php';
    api_ok(dept_jar_cacao_bom_snapshot(ims_pdo()));
}

function api_settings_jar_cacao_bom_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/dept_jar_cacao_settings.php';

    $user = api_require_ims_user();
    try {
        $saved = dept_jar_cacao_bom_save(
            ims_pdo(),
            (float) ($input['grams_per_jar'] ?? 0),
            (float) ($input['grams_per_bag'] ?? 0),
            api_settings_bool($input['bag_pool_deduct'] ?? true, true),
            (int) ($user['id'] ?? 0) ?: null,
            isset($input['freebie_grams']) ? (float) $input['freebie_grams'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }

    api_ok(array_merge($saved, dept_jar_cacao_bom_snapshot(ims_pdo())));
}

function api_settings_admin_password_get(): void
{
    api_method_allowed(['GET']);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_ok([
        'configured' => admin_override_password_configured(ims_pdo()),
    ]);
}

function api_settings_admin_password_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    $user = api_require_ims_user();
    $newPassword = (string) ($input['new_password'] ?? '');
    $confirm = (string) ($input['new_password_confirm'] ?? $input['confirm_password'] ?? '');
    if ($newPassword !== $confirm) {
        api_fail('New password and confirmation do not match.', 422);
    }
    $current = isset($input['current_password']) ? (string) $input['current_password'] : null;
    try {
        admin_override_password_set(
            ims_pdo(),
            $newPassword,
            $current,
            (int) ($user['id'] ?? 0) ?: null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['configured' => true, 'saved' => true]);
}

function api_settings_pyxacct_sync_get(): void
{
    api_method_allowed(['GET']);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/pyxacct_ap_sync.php';
    api_ok(pyxacct_api_settings_public(ims_pdo()));
}

function api_settings_pyxacct_sync_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/pyxacct_ap_sync.php';
    $user = api_require_ims_user();
    $enabled = api_settings_bool($input['enabled'] ?? true, true);
    $baseUrl = trim((string) ($input['api_base_url'] ?? ''));
    $tokenRaw = $input['api_token'] ?? null;
    $token = null;
    if (array_key_exists('api_token', $input)) {
        $token = is_string($tokenRaw) ? $tokenRaw : (string) $tokenRaw;
    }
    try {
        $public = pyxacct_api_settings_save(
            ims_pdo(),
            $enabled,
            $baseUrl,
            $token,
            (int) ($user['id'] ?? 0) ?: null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(array_merge($public, ['saved' => true]));
}

function api_settings_pyxacct_sync_test(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_settings_require_executive();
    require_once dirname(__DIR__, 2) . '/includes/pyxacct_ap_sync.php';
    $result = pyxacct_test_connection(ims_pdo());
    if (empty($result['ok'])) {
        api_fail((string) ($result['error'] ?? 'Connection test failed.'), 422, $result);
    }
    api_ok($result);
}

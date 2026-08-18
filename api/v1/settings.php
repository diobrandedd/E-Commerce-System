<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * System settings API (executive).
 *
 *   GET  ?action=pos_pins_list
 *   POST ?action=pos_pin_update   -> { id, pin, pin_confirm, label?, is_active? }
 *   POST ?action=pos_pin_create   -> { pin, pin_confirm, label? }
 */

api_dispatch([
    'pos_pins_list' => 'api_settings_pos_pins_list',
    'pos_pin_update' => 'api_settings_pos_pin_update',
    'pos_pin_create' => 'api_settings_pos_pin_create',
]);

function api_settings_require_executive(): void
{
    api_require_permission('settings.manage');
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

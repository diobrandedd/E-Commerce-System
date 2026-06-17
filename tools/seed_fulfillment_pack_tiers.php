<?php

declare(strict_types=1);

/**
 * Seed fulfillment parcel pack tiers (run after migration_phase33).
 * Usage: php tools/seed_fulfillment_pack_tiers.php
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/parcel_pack_costing.php';
require_once dirname(__DIR__) . '/includes/fulfillment_pack_tier_seed.php';

$pdo = rts_pdo();
$result = fulfillment_pack_tier_seed($pdo);
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

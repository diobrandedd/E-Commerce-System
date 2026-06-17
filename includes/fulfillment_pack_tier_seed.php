<?php

declare(strict_types=1);

/**
 * Seed parcel pack tiers from ops spreadsheet (2026-06-17).
 * Run: php tools/seed_fulfillment_pack_tiers.php
 */

/** @return array<string, int> */
function fulfillment_pack_stock_ids_by_name(PDO $pdo): array
{
    $st = $pdo->query(
        "SELECT id, name FROM stocks WHERE supplier_id IS NULL AND is_active = 1
         AND (
           name LIKE 'Bubble wrap roll%'
           OR name LIKE 'Cushion piece%'
           OR name LIKE 'Cushion column roll%'
           OR name LIKE 'Courier pouch%'
           OR name LIKE 'Insulation foam sheet%'
           OR name LIKE 'Cardboard box%'
         )"
    );
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $map[(string) $row['name']] = (int) $row['id'];
    }

    return $map;
}

/** @return list<array{family: string, tier_key: string, label: string, match_rule: array<string, mixed>, sort_order: int, lines: list<array{stock: string, qty: float, unit: string}>}> */
function fulfillment_pack_tier_definitions(): array
{
    return [
        // --- Bulk Shopee (priority 1-10) ---
        [
            'family' => 'honey', 'tier_key' => 'bulk_18x_350ml', 'label' => 'Bulk 18x 350ml',
            'sort_order' => 5,
            'match_rule' => ['bulk_350ml_exact' => 18],
            'lines' => [
                ['stock' => 'Cushion column roll - 35 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 16 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 3, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_21x_350ml', 'label' => 'Bulk 21x 350ml',
            'sort_order' => 6,
            'match_rule' => ['bulk_350ml_exact' => 21],
            'lines' => [
                ['stock' => 'Cushion column roll - 35 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 16 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 3, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_24x_350ml', 'label' => 'Bulk 24x 350ml',
            'sort_order' => 7,
            'match_rule' => ['bulk_350ml_exact' => 24],
            'lines' => [
                ['stock' => 'Cushion column roll - 35 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 16 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 3, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_36x_350ml', 'label' => 'Bulk 36x 350ml',
            'sort_order' => 8,
            'match_rule' => ['bulk_350ml_exact' => 36],
            'lines' => [
                ['stock' => 'Cushion column roll - 40 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 60 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 5, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_21x_250ml', 'label' => 'Bulk 21x 250ml',
            'sort_order' => 9,
            'match_rule' => ['bulk_250ml_exact' => 21],
            'lines' => [
                ['stock' => 'Cushion column roll - 35 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 16 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 3, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_24x_250ml', 'label' => 'Bulk 24x 250ml',
            'sort_order' => 10,
            'match_rule' => ['bulk_250ml_exact' => 24],
            'lines' => [
                ['stock' => 'Cushion column roll - 35 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 16 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 3, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'bulk_42x_250ml', 'label' => 'Bulk 42x 250ml',
            'sort_order' => 11,
            'match_rule' => ['bulk_250ml_exact' => 42],
            'lines' => [
                ['stock' => 'Cushion column roll - 40 GAL', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cushion column roll - 60 LAD', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Cardboard box - whole', 'qty' => 0.4375, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 5, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
            ],
        ],
        // --- Honey comb ---
        [
            'family' => 'honey', 'tier_key' => 'honey_comb_solo', 'label' => 'Honey comb',
            'sort_order' => 20,
            'match_rule' => ['honey_comb_min' => 1],
            'lines' => [
                ['stock' => 'Cushion piece - honey comb', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Cardboard box - whole', 'qty' => 0.5, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
            ],
        ],
        // --- Combos (30-59) ---
        [
            'family' => 'honey', 'tier_key' => 'combo_pt_1L_honey', 'label' => 'Paragis Tea + 1 Liter Honey',
            'sort_order' => 30,
            'match_rule' => ['dept_pt_min' => 1, 'honey_1L_count' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_pt_350ml_honey', 'label' => 'Paragis Tea + 350ml Honey',
            'sort_order' => 31,
            'match_rule' => ['dept_pt_min' => 1, 'honey_350ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_pt_250ml_honey', 'label' => 'Paragis Tea + 250ml Honey',
            'sort_order' => 32,
            'match_rule' => ['dept_pt_min' => 1, 'honey_250ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_brc_1L_honey', 'label' => 'BRC + 1 Liter Honey',
            'sort_order' => 33,
            'match_rule' => ['dept_brc_min' => 1, 'honey_1L_count' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_brc_350ml_honey', 'label' => 'BRC + 350ml Honey',
            'sort_order' => 34,
            'match_rule' => ['dept_brc_min' => 1, 'honey_350ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_brc_250ml_honey', 'label' => 'BRC + 250ml Honey',
            'sort_order' => 35,
            'match_rule' => ['dept_brc_min' => 1, 'honey_250ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_cacao_1L_honey', 'label' => 'Jar Cacao + 1 Liter Honey',
            'sort_order' => 36,
            'match_rule' => ['jar_units_min' => 1, 'honey_1L_count' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 2 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_cacao_350ml_honey', 'label' => 'Jar Cacao + 350ml Honey',
            'sort_order' => 37,
            'match_rule' => ['jar_units_min' => 1, 'honey_350ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_cacao_250ml_honey', 'label' => 'Jar Cacao + 250ml Honey',
            'sort_order' => 38,
            'match_rule' => ['jar_units_min' => 1, 'honey_250ml' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'combo_cacao_1L_honey_3L_cacao', 'label' => '3 Liter Honey + Cacao',
            'sort_order' => 39,
            'match_rule' => ['honey_1L_count' => 3, 'jar_units_min' => 1, 'combo' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 2 liters', 'qty' => 2, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        // --- Honey solo (60-89) ---
        [
            'family' => 'honey', 'tier_key' => 'honey_1x_1L', 'label' => 'Honey - 1x 1L',
            'sort_order' => 60,
            'match_rule' => ['honey_1L_count' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_2x_1L', 'label' => 'Honey - 2x 1L',
            'sort_order' => 61,
            'match_rule' => ['honey_1L_count' => 2, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 2 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_2x_1L_250ml', 'label' => 'Honey - 2x 1L + 250ml',
            'sort_order' => 62,
            'match_rule' => ['honey_1L_count' => 2, 'honey_250ml' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 2 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_3x_1L', 'label' => 'Honey - 3x 1L',
            'sort_order' => 63,
            'match_rule' => ['honey_1L_count' => 3, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 3 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_3x_1L_350ml', 'label' => 'Honey - 3x 1L + 350ml',
            'sort_order' => 64,
            'match_rule' => ['honey_1L_count' => 3, 'honey_350ml' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 3 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_350ml_solo', 'label' => 'Honey - 350ml solo',
            'sort_order' => 65,
            'match_rule' => ['honey_350ml' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 0.5, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_3x_350ml', 'label' => 'Honey - 3x 350ml',
            'sort_order' => 66,
            'match_rule' => ['honey_350ml' => 3, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_250ml_solo', 'label' => 'Honey - 250ml solo',
            'sort_order' => 67,
            'match_rule' => ['honey_250ml' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 0.5, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_1x_gallon', 'label' => 'Honey - 1 gallon',
            'sort_order' => 68,
            'match_rule' => ['honey_gallon_count' => 1, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion column roll - 25', 'qty' => 1, 'unit' => 'roll'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
            ],
        ],
        [
            'family' => 'honey', 'tier_key' => 'honey_2x_gallon', 'label' => 'Honey - 2 gallon',
            'sort_order' => 69,
            'match_rule' => ['honey_gallon_count' => 2, 'solo_honey' => true],
            'lines' => [
                ['stock' => 'Cushion column roll - 25', 'qty' => 2, 'unit' => 'roll'],
                ['stock' => 'Bubble wrap roll - 22 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
            ],
        ],
        // --- Dept solo (100-119) ---
        [
            'family' => 'dept_pt_brc', 'tier_key' => 'dept_pt_1_2', 'label' => 'PT - 1-2 units',
            'sort_order' => 100,
            'match_rule' => ['dept_pt_min' => 1, 'dept_pt_max' => 2, 'solo_dept_pt' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - medium', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'dept_pt_brc', 'tier_key' => 'dept_pt_3_5', 'label' => 'PT - 3-5 units',
            'sort_order' => 101,
            'match_rule' => ['dept_pt_min' => 3, 'dept_pt_max' => 5, 'solo_dept_pt' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'dept_pt_brc', 'tier_key' => 'dept_pt_10_12', 'label' => 'PT - 10-12 units',
            'sort_order' => 102,
            'match_rule' => ['dept_pt_min' => 10, 'dept_pt_max' => 12, 'solo_dept_pt' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 3 liters', 'qty' => 2, 'unit' => 'pcs'],
                ['stock' => 'Bubble wrap roll - 17 cm', 'qty' => 1, 'unit' => 'bubble_piece'],
                ['stock' => 'Bubble wrap roll - 13 cm', 'qty' => 2, 'unit' => 'bubble_piece'],
                ['stock' => 'Courier pouch - x-large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'dept_pt_brc', 'tier_key' => 'dept_brc_1_2', 'label' => 'BRC - 1-2 units',
            'sort_order' => 110,
            'match_rule' => ['dept_brc_min' => 1, 'dept_brc_max' => 2, 'solo_dept_brc' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - medium', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'dept_pt_brc', 'tier_key' => 'dept_brc_3_5', 'label' => 'BRC - 3-5 units',
            'sort_order' => 111,
            'match_rule' => ['dept_brc_min' => 3, 'dept_brc_max' => 5, 'solo_dept_brc' => true],
            'lines' => [
                ['stock' => 'Cushion piece - LAD', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        // --- Cacao solo (130-139) ---
        [
            'family' => 'jar_cacao', 'tier_key' => 'cacao_1_jar', 'label' => 'Cacao - 1 jar',
            'sort_order' => 130,
            'match_rule' => ['jar_units' => 1, 'solo_jar' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - medium', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'jar_cacao', 'tier_key' => 'cacao_2_jars', 'label' => 'Cacao - 2 jars',
            'sort_order' => 131,
            'match_rule' => ['jar_units' => 2, 'solo_jar' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 1 liter', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - medium', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 12.5x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'jar_cacao', 'tier_key' => 'cacao_3_4_jars', 'label' => 'Cacao - 3-4 jars',
            'sort_order' => 132,
            'match_rule' => ['jar_units_min' => 3, 'jar_units_max' => 4, 'solo_jar' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 2 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - large', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 12.5x12', 'qty' => 2, 'unit' => 'pcs'],
            ],
        ],
        [
            'family' => 'jar_cacao', 'tier_key' => 'cacao_5_jars', 'label' => 'Cacao - 5 jars',
            'sort_order' => 133,
            'match_rule' => ['jar_units' => 5, 'solo_jar' => true],
            'lines' => [
                ['stock' => 'Cushion piece - 3 liters', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Courier pouch - x-large', 'qty' => 1, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 12.5x12', 'qty' => 2, 'unit' => 'pcs'],
                ['stock' => 'Insulation foam sheet - 9x12', 'qty' => 1, 'unit' => 'pcs'],
            ],
        ],
    ];
}

function fulfillment_pack_tier_seed(PDO $pdo): array
{
    if (!parcel_pack_tables_ready($pdo)) {
        throw new RuntimeException('parcel_pack_tiers table missing — run migration_phase30 first.');
    }

    $stockMap = fulfillment_pack_stock_ids_by_name($pdo);
    if ($stockMap === []) {
        throw new RuntimeException('No fulfillment SKUs found — run migration_phase33 first.');
    }

    $pdo->exec('DELETE l FROM parcel_pack_tier_lines l');
    $pdo->exec('DELETE FROM parcel_pack_tiers');

    $insTier = $pdo->prepare(
        'INSERT INTO parcel_pack_tiers (family, tier_key, label, match_rule, is_active, sort_order)
         VALUES (?, ?, ?, ?, 1, ?)'
    );
    $insLine = $pdo->prepare(
        'INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $seeded = 0;
    foreach (fulfillment_pack_tier_definitions() as $def) {
        $insTier->execute([
            $def['family'],
            $def['tier_key'],
            $def['label'],
            json_encode($def['match_rule'], JSON_UNESCAPED_UNICODE),
            $def['sort_order'],
        ]);
        $tierId = (int) $pdo->lastInsertId();
        $sort = 1;
        foreach ($def['lines'] as $line) {
            $stockName = $line['stock'];
            if (!isset($stockMap[$stockName])) {
                throw new RuntimeException('Missing stock SKU for tier seed: ' . $stockName);
            }
            $qty = (float) $line['qty'];
            $insLine->execute([
                $tierId,
                $stockMap[$stockName],
                $qty,
                $qty,
                $qty,
                $line['unit'],
                $sort++,
            ]);
        }
        $seeded++;
    }

    return ['tiers_seeded' => $seeded, 'stock_skus' => count($stockMap)];
}

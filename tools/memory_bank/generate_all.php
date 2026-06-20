<?php

declare(strict_types=1);

/**
 * Regenerate all Obsidian memory-bank docs under _System_Core/generated/.
 *
 * Usage (from repo root):
 *   php tools/memory_bank/generate_all.php
 */

require_once __DIR__ . '/Generator.php';

$generator = new MemoryBankGenerator();
$generator->run();

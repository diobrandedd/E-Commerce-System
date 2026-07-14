<?php

declare(strict_types=1);

/**
 * Detect queries that look like courier tracking numbers (index-friendly path).
 * Short / spaced / soft text falls through to contains-style LIKE.
 */
function tracking_search_is_tn_shaped(string $q): bool
{
    $q = trim($q);
    if (strlen($q) < 8) {
        return false;
    }
    if (preg_match('/\s/u', $q)) {
        return false;
    }

    return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_.\/]*$/', $q);
}

function tracking_search_escape_like(string $fragment): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $fragment);
}

/**
 * Equality or prefix match on a tracking column (uses indexes when leading %).
 *
 * @return array{0: string, 1: list<string>}
 */
function tracking_search_tn_column_sql(string $columnSql, string $q): array
{
    $q = trim($q);

    return [
        "({$columnSql} = ? OR {$columnSql} LIKE ?)",
        [$q, tracking_search_escape_like($q) . '%'],
    ];
}

/**
 * Classic contains match for free-text (name, items, short q).
 */
function tracking_search_contains_like(string $q): string
{
    return '%' . tracking_search_escape_like(trim($q)) . '%';
}

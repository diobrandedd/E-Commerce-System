<?php

declare(strict_types=1);

/**
 * Shared utilities for Pyx Tracker memory-bank generators.
 */

function mb_repo_root(): string
{
    return dirname(__DIR__, 2);
}

function mb_output_dir(): string
{
    return mb_repo_root() . '/_System_Core/generated';
}

function mb_modules_dir(): string
{
    return mb_output_dir() . '/modules';
}

function mb_rel_path(string $absolute): string
{
    $root = str_replace('\\', '/', mb_repo_root());
    $abs = str_replace('\\', '/', $absolute);
    if (str_starts_with($abs, $root . '/')) {
        return substr($abs, strlen($root) + 1);
    }
    return $abs;
}

/** @return list<string> */
function mb_glob_php(array $dirs): array
{
    $files = [];
    foreach ($dirs as $dir) {
        $full = mb_repo_root() . '/' . ltrim($dir, '/');
        if (!is_dir($full)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
                continue;
            }
            $files[] = $path;
        }
    }
    sort($files);
    return $files;
}

/** @return list<string> */
function mb_glob_ts(array $dirs): array
{
    $files = [];
    foreach ($dirs as $dir) {
        $full = mb_repo_root() . '/' . ltrim($dir, '/');
        if (!is_dir($full)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = $file->getExtension();
            if (!in_array($ext, ['ts', 'tsx'], true)) {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_contains($path, '/node_modules/')) {
                continue;
            }
            $files[] = $path;
        }
    }
    sort($files);
    return $files;
}

function mb_read(string $path): string
{
    $content = file_get_contents($path);
    return is_string($content) ? $content : '';
}

function mb_write(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
}

function mb_module_slug(string $includesFile): string
{
    return pathinfo($includesFile, PATHINFO_FILENAME);
}

function mb_module_wikilink(string $includesFile, ?string $anchor = null): string
{
    $slug = mb_module_slug($includesFile);
    $link = 'modules/' . $slug;
    if ($anchor !== null && $anchor !== '') {
        return '[[' . $link . '#' . $anchor . ']]';
    }
    return '[[' . $link . ']]';
}

/**
 * Resolve a require path relative to the including file.
 */
function mb_resolve_include(string $fromFile, string $expr): ?string
{
    $expr = trim($expr);
    if ($expr === '') {
        return null;
    }

    $fromDir = str_replace('\\', '/', dirname($fromFile));

    if (preg_match("/^__DIR__\s*\.\s*'([^']+)'/", $expr, $m)) {
        $candidate = $fromDir . $m[1];
    } elseif (preg_match('/^__DIR__\s*\.\s*"([^"]+)"/', $expr, $m)) {
        $candidate = $fromDir . $m[1];
    } elseif (preg_match("/dirname\s*\(\s*__DIR__\s*,\s*(\d+)\s*\)\s*\.\s*'([^']+)'/", $expr, $m)) {
        $candidate = dirname($fromDir, (int) $m[1]) . $m[2];
    } elseif (preg_match('/dirname\s*\(\s*__DIR__\s*,\s*(\d+)\s*\)\s*\.\s*"([^"]+)"/', $expr, $m)) {
        $candidate = dirname($fromDir, (int) $m[1]) . $m[2];
    } elseif (preg_match("/^'([^']+\.php)'$/", $expr, $m)) {
        $candidate = $fromDir . '/' . $m[1];
    } elseif (preg_match('/^"([^"]+\.php)"$/', $expr, $m)) {
        $candidate = $fromDir . '/' . $m[1];
    } else {
        return null;
    }

    $candidate = str_replace('\\', '/', $candidate);
    $parts = [];
    foreach (explode('/', $candidate) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }
    $resolved = implode('/', $parts);
    if (is_file($resolved)) {
        return $resolved;
    }
    return null;
}

/** @return list<array{expr: string, target: ?string}> */
function mb_extract_requires(string $file, string $content): array
{
    $results = [];
    if (!preg_match_all('/\brequire(?:_once)?\s*(?:\(\s*)?([^;\)]+)/', $content, $matches, PREG_SET_ORDER)) {
        return $results;
    }
    foreach ($matches as $m) {
        $expr = trim($m[1]);
        $expr = rtrim($expr, ')');
        $target = mb_resolve_include($file, $expr);
        $results[] = ['expr' => $expr, 'target' => $target];
    }
    return $results;
}

/** @return list<string> */
function mb_extract_function_defs(string $content): array
{
    $names = [];
    if (preg_match_all('/\bfunction\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = $name;
        }
    }
    return array_values(array_unique($names));
}

/**
 * Extract function body by brace matching (best-effort).
 */
function mb_extract_function_body(string $content, string $functionName): ?string
{
    $pattern = '/\bfunction\s+' . preg_quote($functionName, '/') . '\s*\([^)]*\)\s*(?::\s*[^{]+)?\{/';
    if (!preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $start = $m[0][1] + strlen($m[0][0]) - 1;
    $len = strlen($content);
    $depth = 0;
    for ($i = $start; $i < $len; $i++) {
        $ch = $content[$i];
        if ($ch === '{') {
            $depth++;
        } elseif ($ch === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($content, $start, $i - $start + 1);
            }
        }
    }
    return null;
}

/** @return list<string> */
function mb_sql_table_denylist(): array
{
    return [
        'a', 'an', 'and', 'as', 'by', 'dual', 'each', 'else', 'end', 'for', 'from', 'if',
        'in', 'into', 'is', 'not', 'null', 'on', 'or', 'select', 'set', 'the', 'then',
        'this', 'to', 'when', 'where', 'with', 'values', 'case', 'left', 'right', 'inner',
        'outer', 'cross', 'natural', 'using', 'limit', 'order', 'group', 'having', 'union',
        'all', 'any', 'some', 'exists', 'between', 'like', 'escape', 'distinct', 'over',
        'partition', 'rows', 'range', 'preceding', 'following', 'current', 'row', 'only',
        'first', 'next', 'last', 'match', 'against', 'boolean', 'mode', 'now', 'curdate',
        'curtime', 'date', 'time', 'timestamp', 'interval', 'year', 'month', 'day', 'hour',
        'minute', 'second', 'microsecond', 'information_schema', 'columns', 'tables',
    ];
}

/** @return list<string> */
function mb_extract_sql_tables(string $text, ?array $knownTables = null): array
{
    $tables = [];
    $deny = array_flip(mb_sql_table_denylist());
    $patterns = [
        '/\bFROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bJOIN\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bINTO\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bUPDATE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bTABLE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches)) {
            foreach ($matches[1] as $table) {
                $lower = strtolower($table);
                if (isset($deny[$lower])) {
                    continue;
                }
                if ($knownTables !== null && !in_array($lower, $knownTables, true)) {
                    continue;
                }
                $tables[] = $lower;
            }
        }
    }
    return array_values(array_unique($tables));
}

/** @return list<string> */
function mb_schema_tables(string $schemaPath): array
{
    if (!is_file($schemaPath)) {
        return [];
    }
    $content = mb_read($schemaPath);
    $tables = [];
    if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $content, $matches)) {
        foreach ($matches[1] as $table) {
            $tables[] = strtolower($table);
        }
    }
    return array_values(array_unique($tables));
}

/** @return list<string> */
function mb_all_schema_tables(string $sqlDir): array
{
    $tables = [];
    foreach (glob($sqlDir . '/*.sql') ?: [] as $file) {
        foreach (mb_schema_tables($file) as $table) {
            $tables[] = $table;
        }
    }
    return array_values(array_unique($tables));
}

/**
 * Find call sites for a function across scanned files.
 *
 * @param list<string> $files
 * @return list<array{file: string, line: int, context: string}>
 */
function mb_find_callers(string $functionName, array $files): array
{
    $callers = [];
    $pattern = '/\b' . preg_quote($functionName, '/') . '\s*\(/';
    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $idx => $line) {
            if (preg_match($pattern, $line) && !preg_match('/\bfunction\s+' . preg_quote($functionName, '/') . '\s*\(/', $line)) {
                $callers[] = [
                    'file' => mb_rel_path($file),
                    'line' => $idx + 1,
                    'context' => trim($line),
                ];
            }
        }
    }
    return $callers;
}

/**
 * Parse api_dispatch map from endpoint file.
 *
 * @return array<string, string> action => handler
 */
function mb_parse_api_dispatch(string $content): array
{
    $actions = [];
    if (!preg_match('/api_dispatch\s*\(\s*\[(.*?)\]\s*\)/s', $content, $m)) {
        return $actions;
    }
    $block = $m[1];
    if (preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $block, $pairs, PREG_SET_ORDER)) {
        foreach ($pairs as $p) {
            $actions[$p[1]] = $p[2];
        }
    }
    return $actions;
}

/**
 * Find domain function calls inside an API handler body.
 *
 * @param list<string>|null $knownDomainFunctions When set, only include calls to these functions.
 * @return list<string>
 */
function mb_handler_domain_calls(string $content, string $handlerName, ?array $knownDomainFunctions = null): array
{
    $body = mb_extract_function_body($content, $handlerName);
    if ($body === null) {
        return [];
    }
    $knownSet = $knownDomainFunctions !== null ? array_flip($knownDomainFunctions) : null;
    $calls = [];
    if (preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $body, $matches)) {
        foreach ($matches[1] as $fn) {
            if (str_starts_with($fn, 'api_')) {
                continue;
            }
            if ($knownSet !== null) {
                if (!isset($knownSet[$fn])) {
                    continue;
                }
            } elseif (!preg_match('/^(honey|rts|supplier|ims|pos|pdf_to_excel|waybill|order_remarks|dashboard|sp_sort|qr_|rts_)/', $fn)) {
                continue;
            }
            $calls[] = $fn;
        }
    }
    return array_values(array_unique($calls));
}

/** @return list<string> */
function mb_all_includes_functions(array $includesFunctions): array
{
    $all = [];
    foreach ($includesFunctions as $functions) {
        foreach ($functions as $fn) {
            $all[] = $fn;
        }
    }
    return array_values(array_unique($all));
}

function mb_is_risky_include(string $from, ?string $to): bool
{
    if ($to === null) {
        return false;
    }
    $fromRel = mb_rel_path($from);
    $toRel = mb_rel_path($to);
    return str_starts_with($fromRel, 'includes/') && str_starts_with($toRel, 'api/v1/');
}

function mb_frontmatter(array $meta): string
{
    $lines = ["---"];
    foreach ($meta as $key => $value) {
        if (is_array($value)) {
            $lines[] = $key . ': ' . json_encode(array_values($value), JSON_UNESCAPED_SLASHES);
        } else {
            $lines[] = $key . ': ' . $value;
        }
    }
    $lines[] = "---";
    return implode("\n", $lines) . "\n\n";
}

function mb_spa_output_dir(): string
{
    return mb_output_dir() . '/spa';
}

/** @return list<array{path: string, component: string, roles: string}> */
function mb_parse_routes_ts(string $content): array
{
    $routes = [];
    if (preg_match_all(
        '/\{\s*path:\s*"([^"]+)"\s*,\s*element:\s*guarded\s*\(\s*(\w+)\s*(?:,\s*\{\s*roles:\s*\[([^\]]+)\]\s*(?:,\s*allowPOS:\s*true\s*)?\})?\s*\)/',
        $content,
        $matches,
        PREG_SET_ORDER
    )) {
        foreach ($matches as $m) {
            $roles = isset($m[3]) ? trim(preg_replace('/["\s]/', '', $m[3]) ?? '') : 'any authenticated';
            $routes[] = ['path' => $m[1], 'component' => $m[2], 'roles' => $roles !== '' ? $roles : 'any authenticated'];
        }
    }
    if (preg_match_all('/\{\s*path:\s*"([^"]+)"\s*,\s*Component:\s*(\w+)/', $content, $plain, PREG_SET_ORDER)) {
        foreach ($plain as $m) {
            if ($m[2] === 'LoginPage' || $m[2] === 'Navigate') {
                continue;
            }
            $exists = false;
            foreach ($routes as $r) {
                if ($r['path'] === $m[1]) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $routes[] = ['path' => $m[1], 'component' => $m[2], 'roles' => 'public or POS nested'];
            }
        }
    }
    return $routes;
}

/** @return array<string, string> token => value */
function mb_parse_theme_tokens(string $css): array
{
    $tokens = [];
    if (preg_match('/:root\s*\{([^}]+)\}/s', $css, $m)) {
        if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/', $m[1], $pairs, PREG_SET_ORDER)) {
            foreach ($pairs as $p) {
                $tokens[$p[1]] = trim($p[2]);
            }
        }
    }
    return $tokens;
}

/** @return list<string> */
function mb_extract_ts_exports(string $content): array
{
    $exports = [];
    if (preg_match_all('/export\s+(?:function|const)\s+(\w+)/', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $exports[] = $name;
        }
    }
    return array_values(array_unique($exports));
}

/** @return list<array{file: string, pattern: string}> */
function mb_scan_ui_patterns(array $tsFiles): array
{
    $patterns = [];
    $checks = [
        'PageLayout' => '/PageLayout/',
        'POSModal' => '/POSModal/',
        'ModalShell' => '/ModalShell/',
        'fixed inset-0' => '/fixed inset-0/',
        'Dialog (Radix)' => '/from "@\/app\/components\/ui\/dialog"/',
        'OrderRemarksToast' => '/OrderRemarksToast/',
        'FeedbackBurst' => '/FeedbackBurst/',
        'useReducedMotion' => '/useReducedMotion/',
    ];
    foreach ($tsFiles as $file) {
        $content = mb_read($file);
        $rel = mb_rel_path($file);
        foreach ($checks as $label => $regex) {
            if (preg_match($regex, $content)) {
                $patterns[] = ['file' => $rel, 'pattern' => $label];
            }
        }
    }
    return $patterns;
}

function mb_anchor(string $name): string
{
    return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '-', $name) ?? $name);
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

final class MemoryBankGenerator
{
    private string $root;
    /** @var list<string> */
    private array $phpFiles = [];
    /** @var list<string> */
    private array $tsFiles = [];
    /** @var list<string> */
    private array $schemaTables = [];
    /** @var array<string, list<string>> */
    private array $includesFunctions = [];
    /** @var array<string, string> file => content cache */
    private array $contentCache = [];

    public function __construct()
    {
        $this->root = mb_repo_root();
    }

    public function run(): void
    {
        $this->loadSources();
        $this->renderApiIndex();
        $this->renderIncludeGraph();
        $this->renderDatabaseTables();
        $this->renderModuleIndex();
        $this->renderFrontendClients();
        $this->renderSpaIndex();
        echo "Memory bank generated in " . mb_output_dir() . PHP_EOL;
    }

    private function loadSources(): void
    {
        $this->phpFiles = mb_glob_php([
            'includes',
            'api',
            'auth',
            'dept',
            'deliveries',
            'exec',
            'prodmgr',
            'pos',
            'sp_sort',
            'orders-editor',
            'waybill-stamper',
            'pdf-to-excel',
        ]);
        $this->phpFiles = array_merge(
            $this->phpFiles,
            array_filter(
                glob($this->root . '/*.php') ?: [],
                static fn (string $f): bool => is_file($f)
            )
        );
        $this->phpFiles = array_values(array_unique($this->phpFiles));
        sort($this->phpFiles);

        $this->tsFiles = mb_glob_ts(['app/src']);
        $this->schemaTables = mb_all_schema_tables($this->root . '/sql');

        foreach ($this->phpFiles as $file) {
            if (str_contains(mb_rel_path($file), 'includes/')) {
                $content = $this->content($file);
                $this->includesFunctions[$file] = mb_extract_function_defs($content);
            }
        }
    }

    private function content(string $file): string
    {
        if (!isset($this->contentCache[$file])) {
            $this->contentCache[$file] = mb_read($file);
        }
        return $this->contentCache[$file];
    }

    private function renderApiIndex(): void
    {
        $domainFunctions = mb_all_includes_functions($this->includesFunctions);

        $lines = [
            mb_frontmatter([
                'title' => 'API Index',
                'generated' => date('c'),
                'type' => 'memory-bank',
            ]),
            '# API Index',
            '',
            '> AUTO-GENERATED — do not edit by hand. Run `php tools/memory_bank/generate_all.php`.',
            '',
            'See also: [[110_Include_Graph]] · [[130_Module_Index]] · [[140_Frontend_API_Clients]]',
            '',
        ];

        $v1Dir = $this->root . '/api/v1';
        $legacyDir = $this->root . '/api';

        $lines[] = '## api/v1 (SPA JSON gateway)';
        $lines[] = '';

        foreach (glob($v1Dir . '/*.php') ?: [] as $file) {
            $base = basename($file);
            if ($base === '_bootstrap.php' || str_starts_with($base, '_')) {
                if ($base !== '_costing_helpers.php') {
                    continue;
                }
            }
            $rel = mb_rel_path($file);
            $content = $this->content($file);
            $endpoint = pathinfo($base, PATHINFO_FILENAME);
            $requires = mb_extract_requires($file, $content);
            $includeTargets = array_filter(array_column($requires, 'target'));

            $lines[] = '### `' . $endpoint . '.php`';
            $lines[] = '';
            $lines[] = '- **Path:** `' . $rel . '`';
            $lines[] = '- **URL:** `/rts/api/v1/' . $endpoint . '.php?action=...`';
            if ($includeTargets !== []) {
                $incList = array_map(static fn (string $t): string => '`' . mb_rel_path($t) . '`', $includeTargets);
                $lines[] = '- **Includes:** ' . implode(', ', $incList);
            }

            $actions = mb_parse_api_dispatch($content);
            if ($actions !== []) {
                $lines[] = '';
                $lines[] = '| Action | Handler | Domain calls |';
                $lines[] = '|--------|---------|--------------|';
                foreach ($actions as $action => $handler) {
                    $domainCalls = mb_handler_domain_calls($content, $handler, $domainFunctions);
                    $domainStr = $domainCalls !== [] ? implode(', ', array_map(static fn (string $c): string => '`' . $c . '()`', array_slice($domainCalls, 0, 8))) : '—';
                    if (count($domainCalls) > 8) {
                        $domainStr .= ', …';
                    }
                    $lines[] = '| `' . $action . '` | `' . $handler . '()` | ' . $domainStr . ' |';
                }
            } else {
                $lines[] = '- **Note:** No `api_dispatch` map (inline handlers or helper file).';
            }
            $lines[] = '';
        }

        $lines[] = '## Legacy api/ (non-v1)';
        $lines[] = '';
        foreach (glob($legacyDir . '/*.php') ?: [] as $file) {
            $rel = mb_rel_path($file);
            $content = $this->content($file);
            $requires = array_filter(array_column(mb_extract_requires($file, $content), 'target'));
            $lines[] = '### `' . basename($file) . '`';
            $lines[] = '- **Path:** `' . $rel . '`';
            if ($requires !== []) {
                $incList = array_map(static fn (string $t): string => '`' . mb_rel_path($t) . '`', $requires);
                $lines[] = '- **Includes:** ' . implode(', ', $incList);
            }
            $lines[] = '';
        }

        mb_write(mb_output_dir() . '/100_API_Index.md', implode("\n", $lines) . "\n");
    }

    private function renderIncludeGraph(): void
    {
        $edges = [];
        $risky = [];

        foreach ($this->phpFiles as $file) {
            $requires = mb_extract_requires($file, $this->content($file));
            foreach ($requires as $req) {
                if ($req['target'] === null) {
                    continue;
                }
                $from = mb_rel_path($file);
                $to = mb_rel_path($req['target']);
                $edges[] = ['from' => $from, 'to' => $to];
                if (mb_is_risky_include($file, $req['target'])) {
                    $risky[] = ['from' => $from, 'to' => $to, 'expr' => $req['expr']];
                }
            }
        }

        usort($edges, static fn (array $a, array $b): int => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

        $lines = [
            mb_frontmatter([
                'title' => 'Include Graph',
                'generated' => date('c'),
                'type' => 'memory-bank',
                'edge_count' => count($edges),
            ]),
            '# Include Graph',
            '',
            '> AUTO-GENERATED — PHP `require` / `require_once` edges.',
            '',
        ];

        if ($risky !== []) {
            $lines[] = '## Risky edges (includes → api/v1)';
            $lines[] = '';
            $lines[] = 'These reverse-layer includes can boot JSON headers/sessions unexpectedly. See [[../003_Fragile_Edges]].';
            $lines[] = '';
            foreach ($risky as $r) {
                $lines[] = '- `' . $r['from'] . '` → `' . $r['to'] . '`';
            }
            $lines[] = '';
        }

        $lines[] = '## All edges';
        $lines[] = '';
        $lines[] = '| From | To |';
        $lines[] = '|------|-----|';
        foreach ($edges as $e) {
            $lines[] = '| `' . $e['from'] . '` | `' . $e['to'] . '` |';
        }

        mb_write(mb_output_dir() . '/110_Include_Graph.md', implode("\n", $lines) . "\n");
    }

    private function renderDatabaseTables(): void
    {
        /** @var array<string, list<array{file: string, function: string}>> */
        $tableUsage = [];
        foreach ($this->schemaTables as $table) {
            $tableUsage[$table] = [];
        }

        $includesFiles = array_filter(
            $this->phpFiles,
            static fn (string $f): bool => str_contains(mb_rel_path($f), 'includes/') || str_contains(mb_rel_path($f), 'api/')
        );

        foreach ($includesFiles as $file) {
            $rel = mb_rel_path($file);
            $content = $this->content($file);
            $functions = mb_extract_function_defs($content);
            if ($functions === []) {
                $tables = mb_extract_sql_tables($content, $this->schemaTables);
                foreach ($tables as $table) {
                    if (isset($tableUsage[$table])) {
                        $tableUsage[$table][] = ['file' => $rel, 'function' => '(file-level)'];
                    }
                }
                continue;
            }
            foreach ($functions as $fn) {
                $body = mb_extract_function_body($content, $fn);
                if ($body === null) {
                    continue;
                }
                foreach (mb_extract_sql_tables($body, $this->schemaTables) as $table) {
                    if (isset($tableUsage[$table])) {
                        $tableUsage[$table][] = ['file' => $rel, 'function' => $fn];
                    }
                }
            }
        }

        $lines = [
            mb_frontmatter([
                'title' => 'Database Tables',
                'generated' => date('c'),
                'type' => 'memory-bank',
                'table_count' => count($this->schemaTables),
            ]),
            '# Database Tables',
            '',
            '> AUTO-GENERATED from `sql/*.sql` + SQL regex scan of `includes/` and `api/`.',
            '',
            'Schema sources: `sql/full_schema.sql` and migration files',
            '',
        ];

        foreach ($this->schemaTables as $table) {
            $lines[] = '## `' . $table . '`';
            $lines[] = '';
            $usage = $tableUsage[$table] ?? [];
            if ($usage === []) {
                $lines[] = '_No references detected in includes/api (may be legacy pages only)._';
            } else {
                $lines[] = '| File | Function |';
                $lines[] = '|------|----------|';
                $seen = [];
                foreach ($usage as $u) {
                    $key = $u['file'] . '|' . $u['function'];
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $fnLink = $u['function'] !== '(file-level)'
                        ? mb_module_wikilink($this->root . '/' . dirname($u['file']) . '/' . basename($u['file']), mb_anchor($u['function']))
                        : '`' . $u['function'] . '`';
                    $lines[] = '| `' . $u['file'] . '` | ' . $fnLink . ' |';
                }
            }
            $lines[] = '';
        }

        mb_write(mb_output_dir() . '/120_Database_Tables.md', implode("\n", $lines) . "\n");
    }

    private function renderModuleIndex(): void
    {
        $modulesDir = mb_modules_dir();
        if (is_dir($modulesDir)) {
            foreach (glob($modulesDir . '/*.md') ?: [] as $old) {
                if (is_file($old)) {
                    unlink($old);
                }
            }
        }

        $indexLines = [
            mb_frontmatter([
                'title' => 'Module Index',
                'generated' => date('c'),
                'type' => 'memory-bank',
            ]),
            '# Module Index (`includes/`)',
            '',
            '> AUTO-GENERATED — one note per includes file. Use `php tools/memory_bank/impact.php <function>` before edits.',
            '',
            '| Module | Functions |',
            '|--------|-----------|',
        ];

        $includesFiles = array_filter(
            $this->phpFiles,
            static fn (string $f): bool => str_contains(mb_rel_path($f), 'includes/')
        );
        sort($includesFiles);

        $searchFiles = array_merge($this->phpFiles, $this->tsFiles);

        foreach ($includesFiles as $file) {
            $slug = mb_module_slug($file);
            $rel = mb_rel_path($file);
            $content = $this->content($file);
            $functions = $this->includesFunctions[$file] ?? [];

            $indexLines[] = '| [[' . 'modules/' . $slug . ']] | ' . count($functions) . ' |';

            $modLines = [
                mb_frontmatter([
                    'title' => $slug,
                    'source' => $rel,
                    'generated' => date('c'),
                    'type' => 'module',
                    'function_count' => count($functions),
                ]),
                '# ' . $slug,
                '',
                '> Source: `' . $rel . '`',
                '',
                '[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]',
                '',
            ];

            $requires = mb_extract_requires($file, $content);
            $incTargets = array_filter(array_column($requires, 'target'));
            if ($incTargets !== []) {
                $modLines[] = '## Includes';
                $modLines[] = '';
                foreach ($incTargets as $t) {
                    $modLines[] = '- `' . mb_rel_path($t) . '`';
                }
                $modLines[] = '';
            }

            foreach ($functions as $fn) {
                $anchor = mb_anchor($fn);
                $modLines[] = '## ' . $fn . ' {#'. $anchor . '}';
                $modLines[] = '';

                $body = mb_extract_function_body($content, $fn);
                $tables = $body !== null ? mb_extract_sql_tables($body, $this->schemaTables) : [];
                if ($tables !== []) {
                    $modLines[] = '**Tables:** ' . implode(', ', array_map(static fn (string $t): string => '`' . $t . '`', $tables));
                    $modLines[] = '';
                }

                $callers = mb_find_callers($fn, $searchFiles);
                $callerCount = count($callers);
                $modLines[] = '**Callers (' . $callerCount . '):**';
                $modLines[] = '';
                if ($callers === []) {
                    $modLines[] = '_No direct callers detected (may be entry point or dynamic call)._';
                } else {
                    foreach (array_slice($callers, 0, 25) as $c) {
                        $modLines[] = '- `' . $c['file'] . '`:' . $c['line'] . ' — `' . $c['context'] . '`';
                    }
                    if ($callerCount > 25) {
                        $modLines[] = '- _… and ' . ($callerCount - 25) . ' more (run impact.php for full list)_';
                    }
                }
                $modLines[] = '';
            }

            mb_write(mb_modules_dir() . '/' . $slug . '.md', implode("\n", $modLines) . "\n");
        }

        mb_write(mb_output_dir() . '/130_Module_Index.md', implode("\n", $indexLines) . "\n");
    }

    private function renderFrontendClients(): void
    {
        $entries = [];

        foreach ($this->tsFiles as $file) {
            $rel = mb_rel_path($file);
            $content = $this->content($file);
            $patterns = [
                '/api(?:Get|Post|Put|Delete|Patch)?\s*\(\s*[\'"]([^\'"]+)[\'"]/',
                '/fetch\s*\(\s*`([^`]+)`/',
                '/fetch\s*\(\s*[\'"]([^\'"]+)[\'"]/',
                '/`\$\{API_BASE\}([^`]+)`/',
                '/API_BASE\s*\+\s*[\'"]([^\'"]+)[\'"]/',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $path) {
                        $path = trim($path);
                        if ($path === '' || str_starts_with($path, 'http')) {
                            continue;
                        }
                        if (!str_contains($path, '.php') && !str_contains($path, '/rts/api')) {
                            if (str_starts_with($path, '/')) {
                                // ok
                            } elseif (!preg_match('/^[a-z_]+\.php/', $path)) {
                                continue;
                            }
                        }
                        $entries[] = [
                            'file' => $rel,
                            'path' => $path,
                        ];
                    }
                }
            }

            if (preg_match_all('/action[=:]\s*[\'"]([a-z_]+)[\'"]/', $content, $actionMatches)) {
                foreach ($actionMatches[1] as $action) {
                    $entries[] = [
                        'file' => $rel,
                        'path' => '(action: ' . $action . ')',
                    ];
                }
            }
        }

        usort($entries, static fn (array $a, array $b): int => [$a['path'], $a['file']] <=> [$b['path'], $b['file']]);

        $lines = [
            mb_frontmatter([
                'title' => 'Frontend API Clients',
                'generated' => date('c'),
                'type' => 'memory-bank',
            ]),
            '# Frontend API Clients',
            '',
            '> AUTO-GENERATED from `app/src/**/*.ts(x)` fetch/api calls.',
            '',
            'Backend index: [[100_API_Index]]',
            '',
            '| Client file | API path / action |',
            '|-------------|-------------------|',
        ];

        $seen = [];
        foreach ($entries as $e) {
            $key = $e['file'] . '|' . $e['path'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $lines[] = '| `' . $e['file'] . '` | `' . $e['path'] . '` |';
        }

        mb_write(mb_output_dir() . '/140_Frontend_API_Clients.md', implode("\n", $lines) . "\n");
    }

    private function renderSpaIndex(): void
    {
        $spaDir = mb_spa_output_dir();
        if (!is_dir($spaDir)) {
            mkdir($spaDir, 0755, true);
        }

        $routesFile = $this->root . '/app/src/app/routes.ts';
        $routesContent = is_file($routesFile) ? mb_read($routesFile) : '';
        $routes = mb_parse_routes_ts($routesContent);

        $routeLines = [
            mb_frontmatter(['title' => 'SPA Routes', 'generated' => date('c'), 'type' => 'spa']),
            '# SPA Routes',
            '',
            '> AUTO-GENERATED from `app/src/app/routes.ts`. Manual UX rules: [[../../004_SPA_Design_System]] · [[../../005_SPA_UX_Patterns]]',
            '',
            'Basename: `/rts/app/dist` (prod) · dev at `localhost:5173`',
            '',
            '| Path | Page component | Roles |',
            '|------|----------------|-------|',
        ];
        foreach ($routes as $r) {
            $routeLines[] = '| `/' . $r['path'] . '` | `' . $r['component'] . '` | ' . $r['roles'] . ' |';
        }
        mb_write($spaDir . '/150_Routes.md', implode("\n", $routeLines) . "\n");

        $themeFile = $this->root . '/app/src/styles/theme.css';
        $tokens = is_file($themeFile) ? mb_parse_theme_tokens(mb_read($themeFile)) : [];
        $themeLines = [
            mb_frontmatter(['title' => 'Theme Tokens', 'generated' => date('c'), 'type' => 'spa']),
            '# Theme Tokens',
            '',
            '> AUTO-GENERATED from `app/src/styles/theme.css` `:root`. Design rules: [[../../004_SPA_Design_System]]',
            '',
            '| Token | Value | Tailwind class |',
            '|-------|-------|----------------|',
        ];
        $tailwindMap = [
            'background' => 'bg-background',
            'foreground' => 'text-foreground',
            'primary' => 'bg-primary / text-primary',
            'muted-foreground' => 'text-muted-foreground',
            'border' => 'border-border',
            'card' => 'bg-card',
            'destructive' => 'text-destructive / bg-destructive',
            'accent' => 'bg-accent',
            'ring' => 'ring-ring',
        ];
        foreach ($tokens as $name => $value) {
            if (!isset($tailwindMap[$name]) && !str_starts_with($name, 'sidebar') && $name !== 'radius' && $name !== 'font-size') {
                continue;
            }
            $tw = $tailwindMap[$name] ?? ('(see theme.css)');
            $themeLines[] = '| `--' . $name . '` | `' . $value . '` | `' . $tw . '` |';
        }
        mb_write($spaDir . '/153_Theme_Tokens.md', implode("\n", $themeLines) . "\n");

        $motionFiles = array_filter(
            $this->tsFiles,
            static fn (string $f): bool => str_contains(basename($f), 'Motion') || str_contains(basename($f), 'motion')
        );
        $motionLines = [
            mb_frontmatter(['title' => 'Motion Patterns', 'generated' => date('c'), 'type' => 'spa']),
            '# Motion & Feedback Patterns',
            '',
            '> AUTO-GENERATED exports from `*Motion*.tsx` files. Usage rules: [[../../005_SPA_UX_Patterns]]',
            '',
        ];
        foreach ($motionFiles as $file) {
            $rel = mb_rel_path($file);
            $exports = mb_extract_ts_exports($this->content($file));
            $motionLines[] = '## `' . $rel . '`';
            $motionLines[] = '';
            foreach ($exports as $ex) {
                $motionLines[] = '- `' . $ex . '()`';
            }
            $motionLines[] = '';
        }
        mb_write($spaDir . '/152_Motion_Patterns.md', implode("\n", $motionLines) . "\n");

        $componentFiles = array_filter(
            $this->tsFiles,
            static fn (string $f): bool => str_contains(mb_rel_path($f), 'app/src/app/components/') || str_contains(mb_rel_path($f), 'app/src/app/pages/')
        );
        $uiPatterns = mb_scan_ui_patterns($componentFiles);
        $byPattern = [];
        foreach ($uiPatterns as $row) {
            $byPattern[$row['pattern']][] = $row['file'];
        }

        $compLines = [
            mb_frontmatter(['title' => 'SPA Components Index', 'generated' => date('c'), 'type' => 'spa']),
            '# SPA Components & UI Pattern Usage',
            '',
            '> AUTO-GENERATED scan of pages/components for layout, modal, toast, and motion usage.',
            '',
            'Design system: [[../../004_SPA_Design_System]] · UX patterns: [[../../005_SPA_UX_Patterns]]',
            '',
        ];
        foreach ($byPattern as $pattern => $files) {
            $compLines[] = '## ' . $pattern;
            $compLines[] = '';
            foreach (array_unique($files) as $f) {
                $compLines[] = '- `' . $f . '`';
            }
            $compLines[] = '';
        }

        $modalFiles = array_filter(
            $this->tsFiles,
            static fn (string $f): bool => str_contains(basename($f), 'Modal') || str_contains(basename($f), 'Drawer')
        );
        $compLines[] = '## Dedicated modal/drawer components';
        $compLines[] = '';
        foreach ($modalFiles as $file) {
            $compLines[] = '- `' . mb_rel_path($file) . '`';
        }
        $compLines[] = '';
        mb_write($spaDir . '/151_Components_Index.md', implode("\n", $compLines) . "\n");

        $spaIndex = [
            mb_frontmatter(['title' => 'SPA Index', 'generated' => date('c'), 'type' => 'spa']),
            '# SPA Auto Index',
            '',
            '| Map | Description |',
            '|-----|-------------|',
            '| [[150_Routes]] | React Router paths + role guards |',
            '| [[151_Components_Index]] | Which pages use PageLayout, modals, toasts |',
            '| [[152_Motion_Patterns]] | Exported motion/feedback helpers |',
            '| [[153_Theme_Tokens]] | CSS variables from theme.css |',
            '| [[../140_Frontend_API_Clients]] | TS → PHP API calls |',
            '',
            'Manual (curated): [[../../004_SPA_Design_System]] · [[../../005_SPA_UX_Patterns]]',
            '',
        ];
        mb_write($spaDir . '/149_SPA_Index.md', implode("\n", $spaIndex) . "\n");
    }

    /**
     * Build in-memory index for impact.php (shared scan).
     *
     * @return array{
     *   phpFiles: list<string>,
     *   tsFiles: list<string>,
     *   includesFunctions: array<string, list<string>>,
     *   apiActions: array<string, array<string, string>>,
     *   schemaTables: list<string>
     * }
     */
    public static function buildIndex(): array
    {
        $gen = new self();
        $gen->loadSources();

        $apiActions = [];
        foreach (glob(mb_repo_root() . '/api/v1/*.php') ?: [] as $file) {
            $endpoint = pathinfo($file, PATHINFO_FILENAME);
            if ($endpoint === '_bootstrap') {
                continue;
            }
            $actions = mb_parse_api_dispatch(mb_read($file));
            if ($actions !== []) {
                $apiActions[$endpoint] = $actions;
            }
        }

        return [
            'phpFiles' => $gen->phpFiles,
            'tsFiles' => $gen->tsFiles,
            'includesFunctions' => $gen->includesFunctions,
            'apiActions' => $apiActions,
            'schemaTables' => $gen->schemaTables,
        ];
    }

    /**
     * Produce impact report for a function name.
     */
    public static function impactReport(string $functionName): string
    {
        $index = self::buildIndex();
        $searchFiles = array_merge($index['phpFiles'], $index['tsFiles']);
        $callers = mb_find_callers($functionName, $searchFiles);

        $definedIn = [];
        $tables = [];
        foreach ($index['includesFunctions'] as $file => $functions) {
            if (!in_array($functionName, $functions, true)) {
                continue;
            }
            $definedIn[] = mb_rel_path($file);
            $content = mb_read($file);
            $body = mb_extract_function_body($content, $functionName);
            if ($body !== null) {
                $tables = array_merge($tables, mb_extract_sql_tables($body, $index['schemaTables']));
            }
        }

        $apiHits = [];
        foreach ($index['apiActions'] as $endpoint => $actions) {
            foreach ($actions as $action => $handler) {
                if ($handler === $functionName) {
                    $apiHits[] = '/rts/api/v1/' . $endpoint . '.php?action=' . $action;
                }
            }
        }

        foreach ($index['phpFiles'] as $file) {
            if (!str_contains(mb_rel_path($file), 'api/v1/')) {
                continue;
            }
            $content = mb_read($file);
            foreach ($index['apiActions'][pathinfo($file, PATHINFO_FILENAME)] ?? [] as $action => $handler) {
                $calls = mb_handler_domain_calls($content, $handler, mb_all_includes_functions($index['includesFunctions']));
                if (in_array($functionName, $calls, true)) {
                    $endpoint = pathinfo($file, PATHINFO_FILENAME);
                    $hit = '/rts/api/v1/' . $endpoint . '.php?action=' . $action . ' (via `' . $handler . '`)';
                    if (!in_array($hit, $apiHits, true)) {
                        $apiHits[] = $hit;
                    }
                }
            }
        }

        $lines = [
            '# Impact report: `' . $functionName . '()`',
            '',
            'Generated: ' . date('c'),
            '',
        ];

        $lines[] = '## Definition';
        $lines[] = '';
        if ($definedIn === []) {
            $lines[] = '_Function not found in `includes/` (may be API handler or legacy-only)._';
        } else {
            foreach ($definedIn as $d) {
                $lines[] = '- `' . $d . '`';
            }
        }
        $lines[] = '';

        $lines[] = '## Database tables touched';
        $lines[] = '';
        $tables = array_values(array_unique($tables));
        if ($tables === []) {
            $lines[] = '_None detected (regex scan)._';
        } else {
            foreach ($tables as $t) {
                $lines[] = '- `' . $t . '` — see [[120_Database_Tables#' . $t . ']]';
            }
        }
        $lines[] = '';

        $lines[] = '## API routes';
        $lines[] = '';
        if ($apiHits === []) {
            $lines[] = '_Not a direct API handler; check callers below._';
        } else {
            foreach ($apiHits as $hit) {
                $lines[] = '- `' . $hit . '`';
            }
        }
        $lines[] = '';

        $lines[] = '## Callers (' . count($callers) . ')';
        $lines[] = '';
        if ($callers === []) {
            $lines[] = '_No direct callers found._';
        } else {
            foreach ($callers as $c) {
                $lines[] = '- `' . $c['file'] . '`:' . $c['line'] . ' — `' . $c['context'] . '`';
            }
        }
        $lines[] = '';

        $lines[] = '---';
        $lines[] = 'Regenerate indexes: `php tools/memory_bank/generate_all.php`';

        return implode("\n", $lines) . "\n";
    }
}

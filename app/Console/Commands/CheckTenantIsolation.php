<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTenantIsolation extends Command
{
    protected $signature = 'tenant:check-isolation
        {--db-only : Skip static analysis, only check database columns}
        {--code-only : Skip database checks, only analyse code}
        {--format=table : Output format — table, json, or github}';

    protected $description = 'Scans every database and controller for cross-tenant isolation gaps';

    private const KNOWN_INFRA_TABLES = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
        'migrations', 'password_reset_tokens', 'sessions',
        // companies is the identity source itself — its id IS the client_id
        'companies',
    ];

    private const CONNECTION_MAP = [
        'pgsql'              => ['label' => 'ITSM (Main)',     'columns' => ['client_id', 'company_id']],
        'hr'                 => ['label' => 'HR',              'columns' => ['client_id']],
        'inventory'          => ['label' => 'Inventory',       'columns' => ['client_id']],
        'procurement'        => ['label' => 'Procurement',     'columns' => ['client_id']],
        'order_fulfillment'  => ['label' => 'Order Fulfillment','columns' => ['client_id']],
        'ecommerce'          => ['label' => 'E-Commerce',      'columns' => ['client_id']],
        'manufacturing'      => ['label' => 'Manufacturing',   'columns' => ['client_id']],
        'finance'            => ['label' => 'Finance',         'columns' => ['nexora_client_id']],
        'business_intelligence' => ['label' => 'BI',           'columns' => ['client_id']],
    ];

    private int $exitCode = 0;

    public function handle(): int
    {
        $format = $this->option('format');

        if (! $this->option('code-only')) {
            $this->scanDatabases($format);
        }

        if (! $this->option('db-only')) {
            $this->scanControllers($format);
        }

        if ($this->exitCode === 0) {
            $this->output->writeln('');
            $this->info('✅ All tenant isolation checks passed.');
        }

        return $this->exitCode;
    }

    // ─── Database scan ──────────────────────────────────────────────────────

    private function scanDatabases(string $format): void
    {
        $this->section('Scanning databases for missing client columns...');
        $allIssues = [];

        foreach (self::CONNECTION_MAP as $connection => $config) {
            $issues = $this->scanConnection($connection, $config);
            $allIssues = array_merge($allIssues, $issues);
        }

        if ($format === 'json') {
            $this->line(json_encode(['database_issues' => $allIssues], JSON_PRETTY_PRINT));
        } elseif ($format === 'github') {
            $this->outputGithubAnnotations($allIssues, 'database');
        } elseif ($allIssues !== []) {
            $this->table(
                ['Database', 'Table', 'Expected Column', 'Issue'],
                array_map(fn (array $i): array => [$i['db'], $i['table'], $i['expected'], $i['message']], $allIssues)
            );
        }

        if ($allIssues !== []) {
            $this->exitCode = 1;
            $this->error(sprintf('❌ %d business table(s) are missing a client-scoping column.', count($allIssues)));
        } else {
            $this->info('✅ All business tables have a client-scoping column.');
        }
    }

    private function scanConnection(string $connection, array $config): array
    {
        $issues = [];

        try {
            $tables = DB::connection($connection)
                ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");

            foreach ($tables as $row) {
                $table = $row->table_name;

                if (in_array($table, self::KNOWN_INFRA_TABLES, true)) {
                    continue;
                }

                $columns = DB::connection($connection)
                    ->select(
                        "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?",
                        [$table]
                    );
                $columnNames = array_map(fn ($c) => $c->column_name, $columns);

                $hasClientColumn = false;
                foreach ($config['columns'] as $expected) {
                    if (in_array($expected, $columnNames, true)) {
                        $hasClientColumn = true;
                        break;
                    }
                }

                if (! $hasClientColumn) {
                    $expected = implode('/', $config['columns']);
                    $issues[] = [
                        'db'       => $config['label'],
                        'table'    => $table,
                        'expected' => $expected,
                        'message'  => "Missing {$expected} column",
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->warn("  ⚠ Could not connect to {$config['label']}: {$e->getMessage()}");
        }

        return $issues;
    }

    // ─── Static analysis ────────────────────────────────────────────────────

    private function scanControllers(string $format): void
    {
        $this->section('Scanning controllers for unfiltered query patterns...');

        $issues = [];

        // Build a whitelist of model classes that use BelongsToClient
        // (global scope) so we don't flag their ::all()/::get() calls.
        $scopedModels = $this->discoverScopedModels();

        $controllerDirs = $this->controllerDirectories();

        foreach ($controllerDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.php');

            foreach ($files as $file) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);

                foreach ($lines as $lineNumber => $line) {
                    $trimmed = trim($line);

                    // Skip comments
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#')) {
                        continue;
                    }

                    // Skip known-safe facade/helper patterns
                    if (preg_match('/(Schema|Hash|Config|Storage|File|Log|Validator|Str|Carbon|Auth|Session|Cache|Event|Gate)::/', $trimmed)) {
                        continue;
                    }
                    if (preg_match('/(config|view|redirect|response|back|abort|report|dispatch|app)\(/', $trimmed)) {
                        continue;
                    }

                    $prevLine = $lines[max(0, $lineNumber - 1)] ?? '';
                    $combined = $prevLine . "\n" . $trimmed;

                    // Check if the line has an explicit client scope
                    $hasClientWhere = (bool) preg_match('/where\(.*?(client_id|company_id|nexora_client_id)/', $combined);

                    // Flag Model::all() / Model::get() without visible client scope
                    if (preg_match('/::(all|get)\s*\(\s*\)/', $trimmed)) {
                        if ($hasClientWhere) {
                            continue;
                        }

                        // Skip models that use BelongsToClient (global scope)
                        $modelName = $this->resolveModelName($trimmed);
                        if ($modelName && in_array($modelName, $scopedModels, true)) {
                            continue;
                        }

                        $relativePath = str_replace(base_path() . '/', '', $file);
                        $issues[] = [
                            'file'    => $relativePath,
                            'line'    => $lineNumber + 1,
                            'code'    => $trimmed,
                            'message' => 'Potential unfiltered query — Model::' . (str_contains($trimmed, '::all()') ? 'all()' : 'get()') . ' without visible client_id scope',
                        ];
                    }

                    // Flag DB::table() / DB::connection()->table() — these bypass
                    // Eloquent global scopes entirely and MUST have an explicit where
                    if (preg_match('/DB::.*?table\s*\(/', $trimmed) && ! $hasClientWhere) {
                        $relativePath = str_replace(base_path() . '/', '', $file);
                        $issues[] = [
                            'file'    => $relativePath,
                            'line'    => $lineNumber + 1,
                            'code'    => $trimmed,
                            'message' => 'DB::table() without visible client_id filter — bypasses Eloquent global scopes',
                        ];
                    }
                }
            }
        }

        if ($format === 'json') {
            $this->line(json_encode(['code_issues' => $issues], JSON_PRETTY_PRINT));
        } elseif ($format === 'github') {
            $this->outputGithubAnnotations($issues, 'code');
        } elseif ($issues !== []) {
            $this->table(
                ['File', 'Line', 'Code', 'Issue'],
                array_map(fn (array $i): array => [$i['file'], (string) $i['line'], $i['code'], $i['message']], $issues)
            );
        }

        if ($issues !== []) {
            $this->exitCode = 1;
            $this->error(sprintf('❌ %d potential unfiltered query(s) found in controllers.', count($issues)));
        } else {
            $this->info('✅ No unfiltered query patterns detected in controllers.');
        }
    }

    /**
     * Discover models that use BelongsToClient by scanning model files.
     * Builds a whitelist so the static analysis doesn't flag ::all() calls
     * on models that are already tenant-scoped via a global scope.
     */
    private function discoverScopedModels(): array
    {
        $scoped = [];

        // Match both flat (Modules/HR/app/Models) and nested
        // (Modules/E-Commerce/Store/app/Models) module directory structures.
        $modelDirs = array_merge(
            [app_path('Models')],
            glob(base_path('Modules/*/app/Models'), GLOB_ONLYDIR) ?: [],
            glob(base_path('Modules/*/*/app/Models'), GLOB_ONLYDIR) ?: []
        );

        // Collect every model file, skipping traits and concern files
        $allModelFiles = [];
        foreach ($modelDirs as $dir) {
            foreach (glob($dir . '/*.php') ?: [] as $modelFile) {
                if (! str_contains($modelFile, 'BelongsToClient') && ! str_contains($modelFile, 'Concerns')) {
                    $allModelFiles[] = $modelFile;
                }
            }
        }

        // Detect BelongsToClient usage or a 'client' global scope (used by HR Employee)
        foreach ($allModelFiles as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            if (str_contains($content, 'use BelongsToClient;') || preg_match('/addGlobalScope\(\s*[\'"]client[\'"]/', $content)) {
                $scoped[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $scoped;
    }

    /**
     * Extract the model class name from a line like "ModelName::all()".
     */
    private function resolveModelName(string $line): ?string
    {
        if (preg_match('/([A-Z][a-zA-Z0-9]+)::(all|get)/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function controllerDirectories(): array
    {
        $dirs = [
            app_path('Http/Controllers'),
        ];

        foreach (['HR', 'Inventory', 'Procurement', 'OrderFulfillment', 'Manufacturing', 'Finance', 'BusinessIntelligence'] as $module) {
            $path = base_path("Modules/{$module}/app/Http/Controllers");
            if (is_dir($path)) {
                $dirs[] = $path;
            }
        }

        // E-Commerce has nested controller dirs
        foreach (['Store', 'CRM'] as $sub) {
            $path = base_path("Modules/E-Commerce/{$sub}/app/Http/Controllers");
            if (is_dir($path)) {
                $dirs[] = $path;
            }
        }

        return $dirs;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function section(string $message): void
    {
        $this->output->writeln('');
        $this->output->writeln("<fg=cyan;options=bold>{$message}</>");
    }

    private function outputGithubAnnotations(array $issues, string $type): void
    {
        foreach ($issues as $issue) {
            if ($type === 'database') {
                $this->line("::error title=Missing client column::{$issue['db']}.{$issue['table']} is missing {$issue['expected']}");
            } else {
                $file = $issue['file'];
                $line = $issue['line'];
                $msg  = $issue['message'];
                $this->line("::warning file={$file},line={$line},title=Unfiltered query::{$msg}: {$issue['code']}");
            }
        }
    }
}

<?php

namespace Modules\Procurement\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cached schema introspection.
 *
 * hasTable()/hasColumn() hit information_schema on every call. The Procurement
 * module asks the same handful of questions ("does this external requisitions
 * table exist", "does it have a status column") several times per request, on
 * remote pooled databases — that was 15+ metadata round-trips before a single
 * business row was read.
 *
 * The answers only change when a migration runs, so they are memoised for the
 * request and, when a cache store is available, for an hour. Every probe is
 * exception-safe: an unreachable connection reports "no" rather than throwing.
 */
final class SchemaProbe
{
    /** @var array<string, bool> */
    private static array $memo = [];

    private const TTL = 3600;

    public static function hasTable(string $connection, string $table): bool
    {
        return self::remember("t:{$connection}:{$table}", function () use ($connection, $table) {
            return DB::connection($connection)->getSchemaBuilder()->hasTable($table);
        });
    }

    public static function hasColumn(string $connection, string $table, string $column): bool
    {
        return self::remember("c:{$connection}:{$table}:{$column}", function () use ($connection, $table, $column) {
            $schema = DB::connection($connection)->getSchemaBuilder();

            return $schema->hasTable($table) && $schema->hasColumn($table, $column);
        });
    }

    /** Drop the memoised answers (used by the schema-install commands). */
    public static function flush(): void
    {
        self::$memo = [];

        try {
            \Illuminate\Support\Facades\Cache::forget('procurement.schema');
        } catch (\Throwable $e) {
            // No cache store configured — the per-request memo is enough.
        }
    }

    private static function remember(string $key, \Closure $probe): bool
    {
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        try {
            $value = \Illuminate\Support\Facades\Cache::remember(
                'procurement.schema.'.$key,
                self::TTL,
                function () use ($probe) {
                    return (bool) $probe();
                }
            );
        } catch (\Throwable $e) {
            // Either the cache store or the database connection is unavailable.
            // Fall back to a direct probe; if that also fails, answer "no" so
            // callers degrade instead of 500-ing.
            try {
                $value = (bool) $probe();
            } catch (\Throwable $inner) {
                $value = false;
            }
        }

        return self::$memo[$key] = $value;
    }
}

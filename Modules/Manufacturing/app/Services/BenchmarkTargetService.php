<?php

namespace Modules\Manufacturing\Services;

class BenchmarkTargetService
{
    protected const RANGE_MAP = [
        'high-end'  => 'HE',
        'mid-range' => 'MR',
        'budget'    => 'BU',
        'office'    => 'OF',
    ];

    // Canonical benchmark buckets (match the CPU_/GPU_/RAM_/Storage_/System_
    // check-id prefixes) mapped from whatever human-readable category/name
    // terms a BOM, bundle, or e-commerce order might actually use.
    public const CATEGORY_ALIASES = [
        'CPU'     => ['cpu', 'processor'],
        'GPU'     => ['gpu', 'graphics', 'video'],
        'RAM'     => ['ram', 'memory'],
        'Storage' => ['storage', 'ssd', 'hdd', 'nvme', 'drive', 'disk'],
        'System'  => ['system', 'case', 'cable', 'power', 'psu', 'motherboard'],
    ];

    // ── Resolve a raw category/name string to a canonical benchmark bucket ──
    public static function canonicalCategory(string $rawCategory, string $rawName = ''): ?string
    {
        $haystack = strtolower(trim($rawCategory . ' ' . $rawName));
        if ($haystack === '') return null;

        foreach (self::CATEGORY_ALIASES as $bucket => $terms) {
            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) return $bucket;
            }
        }

        return null;
    }

    // ── Every canonical bucket present among a work order's parts ───────────
    public static function canonicalCategoriesFor(iterable $parts): array
    {
        $buckets = [];
        foreach ($parts as $part) {
            $category = is_array($part) ? ($part['category'] ?? '') : ($part->category ?? '');
            $name     = is_array($part) ? ($part['name'] ?? '')     : ($part->name ?? '');
            $bucket   = self::canonicalCategory((string) $category, (string) $name);
            if ($bucket) $buckets[$bucket] = true;
        }

        return array_keys($buckets);
    }

    // ── Checks that actually apply to this order's components ───────────────
    // Single source of truth for "which checks matter here" — used both to
    // render the benchmark list and to gate release to Order Fulfillment, so
    // the two can never disagree on how many checks an order actually has.
    public function applicableChecksFor(?string $range, iterable $parts): array
    {
        $partsList = is_array($parts) ? $parts : iterator_to_array($parts);
        $buckets   = self::canonicalCategoriesFor($partsList);

        $hasCpu  = in_array('CPU', $buckets, true);
        $hasCase = collect($partsList)->contains(function ($p) {
            $category = is_array($p) ? ($p['category'] ?? '') : ($p->category ?? '');
            $name     = is_array($p) ? ($p['name'] ?? '')     : ($p->name ?? '');
            return str_contains(strtolower($category . ' ' . $name), 'case');
        });

        $applicable = [];
        foreach ($this->targetsFor($range) as $checkId => $def) {
            [$category] = explode('_', $checkId, 2);

            if ($checkId === 'System_post')   { if (!$hasCpu)  continue; }
            elseif ($checkId === 'System_cables') { if (!$hasCase) continue; }
            elseif (!in_array($category, $buckets, true)) { continue; }

            $applicable[$checkId] = $def;
        }

        return $applicable;
    }

    // ── Targets for a build range ────────────────────────────────────────────
    public function targetsFor(?string $range): array
    {
        $key = self::RANGE_MAP[$range] ?? 'MR';
        // Benchmark definitions belong to the Manufacturing module.  Reading
        // them from the root Nexora config returned an empty set, which meant
        // submitted QC values could not be evaluated consistently.
        return config("manufacturing.benchmarkTargets.$key", []);
    }

    // ── Pass / Warn / Fail verdict ───────────────────────────────────────────
    public function verdictFor(string $checkId, ?string $range, ?float $value): string
    {
        if ($value === null) return '';

        $targets = $this->targetsFor($range);
        $check   = $targets[$checkId] ?? null;
        if (!$check) return '';

        $target   = (float) $check['target'];
        $operator = $check['operator'];

        if ($operator === '>=') {
            if ($value >= $target)         return 'Pass';
            if ($value >= $target * 0.9)   return 'Warn';
            return 'Fail';
        }

        if ($operator === '<=') {
            if ($value <= $target)         return 'Pass';
            if ($value <= $target * 1.1)   return 'Warn';
            return 'Fail';
        }

        return $value == $target ? 'Pass' : 'Fail';
    }
}

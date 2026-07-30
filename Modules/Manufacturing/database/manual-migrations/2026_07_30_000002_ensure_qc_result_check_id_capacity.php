<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    protected $connection = 'manufacturing';

    public function up(): void
    {
        $schema = Schema::connection('manufacturing');

        if (! $schema->hasTable('qc_results') || ! $schema->hasColumn('qc_results', 'check_id')) {
            return;
        }

        // Legacy deployments created check_id as varchar(10), while the
        // actual benchmark identifiers (for example CPU_cinebench) are longer.
        // Repeating this widening is safe on PostgreSQL and repairs databases
        // where the old widening migration was recorded but never applied.
        DB::connection('manufacturing')->statement(
            'ALTER TABLE qc_results ALTER COLUMN check_id TYPE varchar(100)'
        );
    }

    public function down(): void
    {
        // Shrinking this column could discard legitimate QC history.
    }
};

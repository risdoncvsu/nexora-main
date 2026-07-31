<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    protected $connection = 'manufacturing';

    /**
     * check_id was varchar(10), which truncates check ids like "Storage_read"
     * (12 chars) and "Storage_write" (13 chars), causing a 22001 error on
     * insert. Widen it on both tables that store a check_id.
     *
     * Raw SQL rather than Schema::table(...)->change() — this project has no
     * doctrine/dbal installed, which ->change() requires.
     */
    public function up(): void
    {
        $schema = Schema::connection('manufacturing');

        if ($schema->hasTable('qc_results')) {
            DB::connection('manufacturing')->statement(
                'ALTER TABLE qc_results ALTER COLUMN check_id TYPE varchar(30)'
            );
        }

        if ($schema->hasTable('rework_failed_checks')) {
            DB::connection('manufacturing')->statement(
                'ALTER TABLE rework_failed_checks ALTER COLUMN check_id TYPE varchar(30)'
            );
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('manufacturing');

        if ($schema->hasTable('qc_results')) {
            DB::connection('manufacturing')->statement(
                'ALTER TABLE qc_results ALTER COLUMN check_id TYPE varchar(10)'
            );
        }

        if ($schema->hasTable('rework_failed_checks')) {
            DB::connection('manufacturing')->statement(
                'ALTER TABLE rework_failed_checks ALTER COLUMN check_id TYPE varchar(10)'
            );
        }
    }
};

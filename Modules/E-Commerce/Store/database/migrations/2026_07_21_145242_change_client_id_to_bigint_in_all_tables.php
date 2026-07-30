<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = DB::connection('ecommerce')->select("SELECT table_name FROM information_schema.columns WHERE column_name = 'client_id' AND table_schema = 'public'");
        
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            DB::connection('ecommerce')->statement(<<<SQL
                ALTER TABLE "{$tableName}"
                ALTER COLUMN client_id TYPE BIGINT
                USING CASE
                    WHEN client_id::text ~ '^[0-9]+$' THEN client_id::text::bigint
                    ELSE NULL
                END
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not destroy numeric tenant ownership during a rollback.
    }
};

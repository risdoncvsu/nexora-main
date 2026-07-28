<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventory is owned by a separate database, while the actor is an HR
     * employee ID.  A foreign key to a standalone Inventory `users` table
     * therefore rejects legitimate client employees during receiving.  Keep
     * these as audited scalar IDs instead of cross-database foreign keys.
     */
    public function up(): void
    {
        $connection = DB::connection('inventory');
        $schema = Schema::connection('inventory');

        foreach ([
            ['stock_movements', 'performed_by'],
            ['stock_receivings', 'processed_by'],
        ] as [$table, $column]) {
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, $column)) {
                continue;
            }

            $constraints = $connection->select(
                "SELECT tc.constraint_name
                 FROM information_schema.table_constraints tc
                 INNER JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                 WHERE tc.constraint_type = 'FOREIGN KEY'
                   AND tc.table_schema = current_schema()
                   AND tc.table_name = ?
                   AND kcu.column_name = ?",
                [$table, $column],
            );

            foreach ($constraints as $constraint) {
                $name = str_replace('"', '""', (string) $constraint->constraint_name);
                $connection->statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$name}\"");
            }

            $connection->statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$column}\" DROP NOT NULL");
        }
    }

    public function down(): void
    {
        // Do not recreate an invalid cross-database relationship.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'ecommerce';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection('ecommerce');

        if (! $schema->hasColumn('users', 'company_id') || $schema->hasColumn('users', 'client_id')) {
            return;
        }

        $schema->table('users', function (Blueprint $table) {
            $table->renameColumn('company_id', 'client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally non-destructive: production storefront data is never removed by rollback.
    }
};

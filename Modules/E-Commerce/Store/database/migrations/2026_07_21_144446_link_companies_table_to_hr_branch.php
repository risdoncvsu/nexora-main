<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
        // Client ownership is passed through client_id and Nexora's
        // integration layer. Do not couple Ecommerce to HR/ITSM through a
        // database foreign server or environment-specific credentials.
        try {
            DB::connection('ecommerce')->statement('DROP SERVER IF EXISTS hr_server CASCADE');
        } catch (\Throwable) {
            // A managed database user may not own a server created by an old
            // deployment. The application no longer relies on it either way.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: no foreign server is created by this migration anymore.
    }
};

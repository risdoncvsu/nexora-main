<?php

use Illuminate\Database\Migrations\Migration;

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
        // The schema installer repairs legacy company_id data after every
        // migration. A direct rename fails on fresh and partial schemas.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Client ownership must not be removed from deployed storefronts.
    }
};

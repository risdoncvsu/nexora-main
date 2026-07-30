<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('ecommerce');

        if ($schema->hasTable('storefront_listings') && ! $schema->hasColumn('storefront_listings', 'packaging_bom_id')) {
            $schema->table('storefront_listings', function (Blueprint $table): void {
                $table->unsignedBigInteger('packaging_bom_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Storefront records are shared operational data. Rollbacks must not
        // silently remove their packaging assignments.
    }
};

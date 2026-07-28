<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('custombuilt_configs', 'configurator_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally non-destructive: production storefront data is never removed by rollback.
    }
};

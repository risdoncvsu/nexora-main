<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    protected $connection = 'order_fulfillment';

    public function up(): void
    {
        $schema = Schema::connection('order_fulfillment');

        if (! $schema->hasTable('order_items')) {
            return;
        }

        if (! $schema->hasColumn('order_items', 'product_type')) {
            $schema->table('order_items', function (Blueprint $table): void {
                $table->string('product_type', 50)->nullable();
            });
        }

        if (! $schema->hasColumn('order_items', 'configuration')) {
            $schema->table('order_items', function (Blueprint $table): void {
                $table->json('configuration')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Historical order metadata must remain available for auditability.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('order_fulfillment');

        if ($schema->hasTable('shipments') && ! $schema->hasColumn('shipments', 'delivered_at')) {
            $schema->table('shipments', function (Blueprint $table): void {
                $table->timestamp('delivered_at')->nullable();
            });
        }

        if ($schema->hasTable('orders') && ! $schema->hasColumn('orders', 'delivered_at')) {
            $schema->table('orders', function (Blueprint $table): void {
                $table->timestamp('delivered_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('order_fulfillment');

        if ($schema->hasTable('shipments') && $schema->hasColumn('shipments', 'delivered_at')) {
            $schema->table('shipments', function (Blueprint $table): void {
                $table->dropColumn('delivered_at');
            });
        }

        if ($schema->hasTable('orders') && $schema->hasColumn('orders', 'delivered_at')) {
            $schema->table('orders', function (Blueprint $table): void {
                $table->dropColumn('delivered_at');
            });
        }
    }
};

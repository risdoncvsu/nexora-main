<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Order model uses the BelongsToClient trait which expects an
     * unsignedBigInteger client_id column. The orders table was missing
     * this column entirely, and order_items had it as uuid which doesn't
     * match the integer client_id value the trait injects.
     */
    public function up(): void
    {
        $schema = Schema::connection('ecommerce');

        // 1. Add client_id to the orders table (matches BelongsToClient trait)
        if (! $schema->hasColumn('orders', 'client_id')) {
            $schema->table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('client_id')->nullable()->index()->after('id');
            });
        }

        // 2. Fix order_items.client_id — change from uuid to unsignedBigInteger
        if ($schema->hasColumn('order_items', 'client_id')) {
            // Drop the existing uuid column and re-add as bigInteger
            DB::connection('ecommerce')->statement('ALTER TABLE order_items DROP COLUMN client_id');
            $schema->table('order_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('client_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('ecommerce');

        if ($schema->hasColumn('orders', 'client_id')) {
            $schema->table('orders', function (Blueprint $table): void {
                $table->dropColumn('client_id');
            });
        }

        // Restore uuid column on order_items (approximate)
        if ($schema->hasColumn('order_items', 'client_id')) {
            DB::connection('ecommerce')->statement('ALTER TABLE order_items DROP COLUMN client_id');
            $schema->table('order_items', function (Blueprint $table): void {
                $table->uuid('client_id')->nullable()->index()->after('id');
            });
        }
    }
};

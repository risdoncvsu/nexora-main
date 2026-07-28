<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('manufacturing');

        if ($schema->hasTable('product_boms') && ! $schema->hasColumn('product_boms', 'bom_type')) {
            $schema->table('product_boms', function (Blueprint $table): void {
                $table->string('bom_type', 30)->nullable()->index();
            });
        }

        // Preserve historical finished-product BOMs, while classifying the
        // clearly named legacy packing lists that were created before type
        // tracking existed.
        if ($schema->hasTable('product_boms') && $schema->hasColumn('product_boms', 'bom_type')) {
            DB::connection('manufacturing')->table('product_boms')
                ->whereNull('bom_type')
                ->where(function ($query): void {
                    $query->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", ['%packing%'])
                        ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", ['%packing%'])
                        ->orWhereRaw("LOWER(COALESCE(name, '')) LIKE ?", ['%packaging%']);
                })
                ->update(['bom_type' => 'packaging']);
        }

        if ($schema->hasTable('work_orders') && ! $schema->hasColumn('work_orders', 'work_order_type')) {
            $schema->table('work_orders', function (Blueprint $table): void {
                $table->string('work_order_type', 30)->nullable()->index();
            });
        }
    }

    // Keep these classification fields on rollback. Removing a deployed
    // discriminator could make packaging work orders eligible for QC again.
    public function down(): void
    {
    }
};

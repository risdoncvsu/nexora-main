<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('finance');

        if (! $schema->hasTable('invoice') || $schema->hasColumn('invoice', 'order_id')) {
            return;
        }

        // Existing Finance installations retain their invoices. The UUID link
        // makes a storefront checkout idempotent and client-traceable.
        $schema->table('invoice', function (Blueprint $table): void {
            $table->uuid('order_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('finance');

        if ($schema->hasTable('invoice') && $schema->hasColumn('invoice', 'order_id')) {
            $schema->table('invoice', function (Blueprint $table): void {
                $table->dropColumn('order_id');
            });
        }
    }
};

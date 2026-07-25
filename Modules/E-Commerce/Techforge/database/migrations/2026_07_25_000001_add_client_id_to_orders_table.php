<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    protected $connection = 'ecommerce';

    public function up(): void
    {
        $schema = Schema::connection('ecommerce');

        if (! $schema->hasTable('orders') || $schema->hasColumn('orders', 'client_id')) {
            return;
        }

        $schema->table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Deployed orders need their storefront ownership retained.
    }
};

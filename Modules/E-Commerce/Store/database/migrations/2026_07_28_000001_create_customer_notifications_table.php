<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    public function up(): void
    {
        if (Schema::connection('ecommerce')->hasTable('customer_notifications')) {
            return;
        }

        Schema::connection('ecommerce')->create('customer_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 60)->default('general')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link', 500)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('icon_color', 20)->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: production storefront data is never removed by rollback.
    }
};

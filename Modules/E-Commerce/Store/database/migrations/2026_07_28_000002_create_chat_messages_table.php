<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ecommerce';

    public function up(): void
    {
        if (Schema::connection('ecommerce')->hasTable('chat_messages')) {
            return;
        }

        Schema::connection('ecommerce')->create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->string('sender_type', 20);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index(['client_id', 'user_id', 'created_at']);
            $table->index(['client_id', 'user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: production storefront data is never removed by rollback.
    }
};

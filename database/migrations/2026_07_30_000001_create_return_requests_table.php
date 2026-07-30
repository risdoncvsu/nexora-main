<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::connection('ecommerce')->create('return_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('order_id');
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type'); // 'cancel' or 'return'
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, refunded, completed
            $table->json('items_data')->nullable();
            $table->decimal('refund_amount', 14, 2)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'user_id']);
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->dropIfExists('return_requests');
    }
};

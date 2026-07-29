<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Finance had an invoice bootstrap migration but no equivalent accounts
     * bootstrap. A fresh dedicated Finance database therefore failed as soon
     * as the dashboard or Expenses page queried accounts. Keep this additive
     * and idempotent so existing client data is never altered.
     */
    public function up(): void
    {
        $schema = Schema::connection('finance');

        if ($schema->hasTable('accounts')) {
            return;
        }

        $schema->create('accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nexora_client_id')->index();
            $table->string('name');
            $table->string('account_type', 100);
            $table->string('detail_type')->nullable();
            $table->decimal('balance', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration may be applied to a
        // live Finance database containing client accounting records.
    }
};

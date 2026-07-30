<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ecommerce')->table('return_requests', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::connection('ecommerce')->table('return_requests', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};

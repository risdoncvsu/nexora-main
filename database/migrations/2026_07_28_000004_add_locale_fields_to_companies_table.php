<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('companies', 'country_code')) {
                $table->string('country_code', 8)->nullable()->after('phone_no');
            }

            if (! Schema::hasColumn('companies', 'timezone')) {
                $table->string('timezone', 64)->default('UTC')->after('country_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (Schema::hasColumn('companies', 'timezone')) {
                $table->dropColumn('timezone');
            }

            if (Schema::hasColumn('companies', 'country_code')) {
                $table->dropColumn('country_code');
            }
        });
    }
};

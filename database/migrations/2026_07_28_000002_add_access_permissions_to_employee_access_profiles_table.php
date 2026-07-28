<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_access_profiles')) {
            return;
        }

        if (! Schema::hasColumn('employee_access_profiles', 'access_permissions')) {
            Schema::table('employee_access_profiles', function (Blueprint $table): void {
                $table->json('access_permissions')->nullable();
            });
        }

        // The former module_access values intentionally are not copied: they
        // describe which module to enter, while this column stores actions
        // within a module. Existing access roles remain unchanged.
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_access_profiles')
            && Schema::hasColumn('employee_access_profiles', 'access_permissions')) {
            Schema::table('employee_access_profiles', function (Blueprint $table): void {
                $table->dropColumn('access_permissions');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'role_name')) {
                $table->string('role_name')->nullable();
            }

            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('roles', 'department')) {
                $table->string('department')->nullable();
            }

            if (! Schema::hasColumn('roles', 'permissions')) {
                $table->json('permissions')->nullable();
            }

            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('roles', 'role_name') ? 'role_name' : null,
                Schema::hasColumn('roles', 'description') ? 'description' : null,
                Schema::hasColumn('roles', 'department') ? 'department' : null,
                Schema::hasColumn('roles', 'permissions') ? 'permissions' : null,
                Schema::hasColumn('roles', 'is_active') ? 'is_active' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

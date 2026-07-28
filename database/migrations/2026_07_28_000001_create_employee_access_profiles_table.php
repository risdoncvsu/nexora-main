<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_access_profiles')) {
            return;
        }

        Schema::create('employee_access_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // The HR employee is stored in the separate HR database, so this
            // deliberately remains an indexed scalar rather than a foreign key.
            $table->unsignedBigInteger('employee_id');
            $table->string('access_role', 40)->default('department_employee');
            $table->json('module_access')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_access_profiles');
    }
};

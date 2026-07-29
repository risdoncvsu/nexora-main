<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('compliance_items') && ! Schema::hasColumn('compliance_items', 'file_path')) {
            Schema::table('compliance_items', function (Blueprint $table): void {
                $table->string('file_path')->nullable();
            });
        }

        if (! Schema::hasTable('compliance_permits')) {
            Schema::create('compliance_permits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('title');
                $table->string('issuer');
                $table->date('expiry_date');
                $table->string('status')->default('Active');
                $table->string('file_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('compliance_risk_assessments')) {
            Schema::create('compliance_risk_assessments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('risk_id');
                $table->string('title');
                $table->decimal('inherent_score', 8, 2);
                $table->string('inherent_text');
                $table->unsignedTinyInteger('likelihood');
                $table->unsignedTinyInteger('impact');
                $table->decimal('residual_score', 8, 2);
                $table->string('residual_text');
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['company_id', 'risk_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_risk_assessments');
        Schema::dropIfExists('compliance_permits');

        if (Schema::hasTable('compliance_items') && Schema::hasColumn('compliance_items', 'file_path')) {
            Schema::table('compliance_items', function (Blueprint $table): void {
                $table->dropColumn('file_path');
            });
        }
    }
};

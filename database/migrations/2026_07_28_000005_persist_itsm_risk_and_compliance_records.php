<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('articles') && ! Schema::hasColumn('articles', 'content')) {
            Schema::table('articles', fn (Blueprint $table) => $table->text('content')->nullable());
        }

        if (! Schema::hasTable('risk_mitigation_plans')) {
            Schema::create('risk_mitigation_plans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('risk_assessment_id')->nullable()->index();
                $table->string('title');
                $table->string('owner');
                $table->decimal('budget', 14, 2)->default(0);
                $table->string('status')->default('Draft');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('risk_incidents')) {
            Schema::create('risk_incidents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('incident_no')->unique();
                $table->string('title');
                $table->string('severity');
                $table->text('description')->nullable();
                $table->string('reporter')->nullable();
                $table->string('status')->default('Open');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('compliance_documents')) {
            Schema::create('compliance_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('details');
                $table->string('linked_id');
                $table->string('classification');
                $table->string('status')->default('Active');
                $table->string('file_path')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'linked_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_documents');
        Schema::dropIfExists('risk_incidents');
        Schema::dropIfExists('risk_mitigation_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->index();
            }

            if (! Schema::hasColumn('articles', 'content')) {
                $table->text('content')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('articles', 'company_id') ? 'company_id' : null,
                Schema::hasColumn('articles', 'content') ? 'content' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

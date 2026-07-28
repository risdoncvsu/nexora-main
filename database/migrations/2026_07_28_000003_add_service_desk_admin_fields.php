<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_tickets', 'assigned_to')) {
            Schema::table('service_tickets', function (Blueprint $table): void {
                $table->foreignId('assigned_to')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('articles', 'content')) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->text('content')->nullable()->after('author_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_tickets', 'assigned_to')) {
            Schema::table('service_tickets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('assigned_to');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('hr');

        if (! $schema->hasTable('attendances')) {
            return;
        }

        if (! $schema->hasColumn('attendances', 'client_id')) {
            $schema->table('attendances', function (Blueprint $table): void {
                $table->unsignedBigInteger('client_id')->nullable()->index();
            });
        }

        // Preserve historical attendance while deriving its owner from the HR
        // employee table. Rows without a valid employee are intentionally left
        // unassigned and never shown in a client report.
        DB::connection('hr')->table('attendances')
            ->whereNull('client_id')
            ->orderBy('id')
            ->chunkById(200, function ($attendances): void {
                $employeeIds = $attendances->pluck('employee_id')->filter()->unique()->values();
                if ($employeeIds->isEmpty()) {
                    return;
                }

                $owners = DB::connection('hr')->table('employees')
                    ->whereIn('id', $employeeIds)
                    ->pluck('client_id', 'id');

                foreach ($attendances as $attendance) {
                    $clientId = $owners->get($attendance->employee_id);
                    if ($clientId) {
                        DB::connection('hr')->table('attendances')
                            ->where('id', $attendance->id)
                            ->update(['client_id' => $clientId]);
                    }
                }
            });
    }

    public function down(): void
    {
        $schema = Schema::connection('hr');

        if ($schema->hasTable('attendances') && $schema->hasColumn('attendances', 'client_id')) {
            $schema->table('attendances', function (Blueprint $table): void {
                $table->dropColumn('client_id');
            });
        }
    }
};

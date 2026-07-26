<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureHrEmployeesTable extends Command
{
    protected $signature = 'hr:ensure-employees-table';

    protected $description = 'Create or repair the HR employees table on the dedicated HR database connection';

    public function handle(): int
    {
        $schema = Schema::connection('hr');

        if (! $schema->hasTable('employees')) {
            $schema->create('employees', function (Blueprint $table): void {
                $table->id();
                $table->string('employee_id')->nullable();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('suffix')->nullable();
                $table->string('gender')->nullable();
                $table->string('marital_status')->nullable();
                $table->string('nationality')->nullable();
                $table->string('profile_picture')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('department')->nullable();
                $table->string('position')->nullable();
                $table->date('hire_date')->nullable();
                $table->string('work_schedule')->nullable();
                $table->string('email')->nullable()->unique();
                $table->string('company_email')->nullable()->unique();
                $table->string('temporary_password')->nullable();
                $table->boolean('must_change_password')->default(false);
                $table->string('birth_certificate')->nullable();
                $table->string('curriculum_vitae')->nullable();
                $table->string('valid_id')->nullable();
                $table->string('medical_certificate')->nullable();
                $table->string('signature')->nullable();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->string('approval_status')->default('Active');
                $table->timestamps();
            });

        }

        $hasLegacyEmployeeClientColumn = $schema->hasColumn('employees', 'itsm_company_id');
        $hasEmployeeClientColumn = $schema->hasColumn('employees', 'client_id');

        $schema->table('employees', function (Blueprint $table) use ($schema, $hasLegacyEmployeeClientColumn, $hasEmployeeClientColumn): void {
            if (! $schema->hasColumn('employees', 'company_email')) {
                $table->string('company_email')->nullable()->unique();
            }
            if (! $schema->hasColumn('employees', 'temporary_password')) {
                $table->string('temporary_password')->nullable();
            }
            if (! $schema->hasColumn('employees', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (! $hasEmployeeClientColumn) {
                if ($hasLegacyEmployeeClientColumn) {
                    $table->renameColumn('itsm_company_id', 'client_id');
                } else {
                    $table->unsignedBigInteger('client_id')->nullable()->index();
                }
            }
            if (! $schema->hasColumn('employees', 'approval_status')) {
                $table->string('approval_status')->default('Active');
            }
        });

        if ($hasLegacyEmployeeClientColumn && $hasEmployeeClientColumn) {
            DB::connection('hr')->table('employees')
                ->whereNull('client_id')
                ->whereNotNull('itsm_company_id')
                ->update(['client_id' => DB::raw('itsm_company_id')]);
        }

        if (! $schema->hasTable('departments')) {
            $schema->create('departments', function (Blueprint $table): void {
                $table->id();
                $table->string('department_name')->unique();
                $table->string('department_code')->nullable();
                $table->string('slug')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('attendances')) {
            $schema->create('attendances', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('attendance_date');
                $table->time('time_in')->nullable();
                $table->string('time_in_image')->nullable();
                $table->time('time_out')->nullable();
                $table->string('time_out_image')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->timestamps();
                $table->unique(['employee_id', 'attendance_date']);
            });
        }

        $hasLegacyAttendanceClientColumn = $schema->hasColumn('attendances', 'itsm_company_id');
        $hasAttendanceClientColumn = $schema->hasColumn('attendances', 'client_id');

        if (! $hasAttendanceClientColumn) {
            $schema->table('attendances', function (Blueprint $table) use ($hasLegacyAttendanceClientColumn): void {
                if ($hasLegacyAttendanceClientColumn) {
                    $table->renameColumn('itsm_company_id', 'client_id');
                } else {
                    $table->unsignedBigInteger('client_id')->nullable()->index();
                }
            });
        }

        if ($hasLegacyAttendanceClientColumn && $hasAttendanceClientColumn) {
            DB::connection('hr')->table('attendances')
                ->whereNull('client_id')
                ->whereNotNull('itsm_company_id')
                ->update(['client_id' => DB::raw('itsm_company_id')]);
        }

        $this->backfillClientIds('attendances');

        $this->ensureLeaveRequestsTable($schema);

        $this->info('Verified the HR employees and leave-request tables.');

        return self::SUCCESS;
    }

    /**
     * HR is deployed against a separate connection and this command is what
     * the application runs during startup. Keep the leave schema here rather
     * than relying on the default-connection module migrations.
     */
    private function ensureLeaveRequestsTable($schema): void
    {
        if (! $schema->hasTable('leave_requests')) {
            $schema->create('leave_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id');
                $table->string('type')->nullable();
                $table->date('from_date')->nullable();
                $table->date('to_date')->nullable();
                $table->decimal('total_days', 5, 2)->nullable();
                $table->text('reason')->nullable();
                $table->json('attachments')->nullable();
                $table->string('status')->default('pending')->index();
                $table->text('status_note')->nullable();
                $table->string('reference_id')->nullable()->unique();
                $table->string('reviewed_by_name')->nullable();
                $table->string('reviewed_by_position')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        $schema->table('leave_requests', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('leave_requests', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->index();
            }
            if (! $schema->hasColumn('leave_requests', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'type')) {
                $table->string('type')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'from_date')) {
                $table->date('from_date')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'to_date')) {
                $table->date('to_date')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'total_days')) {
                $table->decimal('total_days', 5, 2)->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'reason')) {
                $table->text('reason')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'attachments')) {
                $table->json('attachments')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'status')) {
                $table->string('status')->default('pending')->index();
            }
            if (! $schema->hasColumn('leave_requests', 'status_note')) {
                $table->text('status_note')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'reference_id')) {
                $table->string('reference_id')->nullable()->unique();
            }
            if (! $schema->hasColumn('leave_requests', 'reviewed_by_name')) {
                $table->string('reviewed_by_name')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'reviewed_by_position')) {
                $table->string('reviewed_by_position')->nullable();
            }
            if (! $schema->hasColumn('leave_requests', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });

        // If a standalone HR deployment created leave requests before this
        // integration, inherit the employee's client so old records are not
        // invisible or exposed across clients after tenant scoping is enabled.
        $this->backfillClientIds('leave_requests');
    }

    private function backfillClientIds(string $table): void
    {
        $database = DB::connection('hr');

        $database->table($table)
            ->select(['id', 'employee_id'])
            ->whereNull('client_id')
            ->orderBy('id')
            ->eachById(function (object $record) use ($database, $table): void {
                $clientId = $database->table('employees')
                    ->where('id', $record->employee_id)
                    ->value('client_id');

                if ($clientId) {
                    $database->table($table)
                        ->where('id', $record->id)
                        ->update(['client_id' => $clientId]);
                }
            });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $schema = Schema::connection('hr');

        if (! $schema->hasTable('delivery_drivers')) {
            $schema->create('delivery_drivers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('courier_provider');
                $table->string('vehicle_type')->nullable();
                $table->string('plate_number')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('availability')->default('AVAILABLE');
                $table->timestamps();
                $table->unique(['client_id', 'employee_id']);
            });

            return;
        }

        $missingColumns = [
            'client_id' => fn (Blueprint $table) => $table->unsignedBigInteger('client_id')->nullable()->index(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable()->index(),
            'courier_provider' => fn (Blueprint $table) => $table->string('courier_provider')->nullable(),
            'vehicle_type' => fn (Blueprint $table) => $table->string('vehicle_type')->nullable(),
            'plate_number' => fn (Blueprint $table) => $table->string('plate_number')->nullable(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true),
            'availability' => fn (Blueprint $table) => $table->string('availability')->default('AVAILABLE'),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        $missingColumns = array_filter(
            $missingColumns,
            fn (callable $addColumn, string $column): bool => ! $schema->hasColumn('delivery_drivers', $column),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($missingColumns) {
            $schema->table('delivery_drivers', function (Blueprint $table) use ($missingColumns): void {
                foreach ($missingColumns as $addColumn) {
                    $addColumn($table);
                }
            });
        }
    }

    public function down(): void
    {
        // Delivery-driver records are owned by HR and are intentionally retained.
    }
};

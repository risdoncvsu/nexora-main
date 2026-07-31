<?php

namespace Tests\Feature;

use Carbon\CarbonInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Manufacturing\Models\WorkOrder;
use Tests\TestCase;

class ManufacturingDashboardChartDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.manufacturing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Schema::connection('manufacturing')->create('work_orders', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('specs')->nullable();
            $table->string('status');
            $table->string('due')->nullable();
            $table->date('due_date')->nullable();
            $table->string('source')->nullable();
            $table->string('fulfillment_order_id')->nullable();
            $table->string('assigned')->nullable();
            $table->integer('assigned_employee_id')->nullable();
            $table->string('range')->nullable();
            $table->string('work_order_type')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('manufacturing')->create('work_order_parts', function (Blueprint $table): void {
            $table->id();
            $table->string('wo_id');
            $table->string('product_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_dashboard_chart_data_endpoint_returns_weekly_build_totals(): void
    {
        $this->withoutMiddleware();
        session(['employee_client_id' => 42]);

        $weekStart = now()->startOfWeek(CarbonInterface::MONDAY);

        WorkOrder::forceCreate([
            'id' => 'WO-1001',
            'name' => 'Build One',
            'status' => 'Completed',
            'due' => 'Due Jan 1',
            'source' => 'ecommerce',
            'assigned' => 'Tech A',
            'created_at' => $weekStart->copy()->addDays(1)->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'client_id' => 42,
        ]);

        WorkOrder::forceCreate([
            'id' => 'WO-1002',
            'name' => 'Build Two',
            'status' => 'Completed',
            'due' => 'Due Jan 2',
            'source' => 'ecommerce',
            'assigned' => 'Tech B',
            'created_at' => $weekStart->copy()->addDays(2)->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'client_id' => 42,
        ]);

        $response = $this->getJson(route('manufacturing.dashboard-chart-data'));

        $response
            ->assertOk()
            ->assertJsonStructure(['days', 'weekCounts'])
            ->assertJsonPath('days.0', 'Mon')
            ->assertJsonPath('days.6', 'Sun')
            ->assertJsonPath('weekCounts.1', 1)
            ->assertJsonPath('weekCounts.2', 1);
    }
}

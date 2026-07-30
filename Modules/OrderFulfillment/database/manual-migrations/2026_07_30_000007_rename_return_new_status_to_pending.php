<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;
    protected $connection = 'order_fulfillment';

    /**
     * "NEW" (a customer return request sitting untouched, awaiting
     * admin review/accept/decline) is being renamed to "Pending" — a
     * clearer label now that the Returns tab no longer shows a
     * Resolution column to spell that state out separately. Every piece
     * of app code (ReturnController, TestPanelController, return.blade.php)
     * is updated alongside this migration to match.
     */
    public function up(): void
    {
        $schema = Schema::connection('order_fulfillment');

        if (! $schema->hasTable('returns')) {
            return;
        }

        DB::connection('order_fulfillment')
            ->table('returns')
            ->where('status', 'NEW')
            ->update(['status' => 'Pending']);
    }

    public function down(): void
    {
        $schema = Schema::connection('order_fulfillment');

        if (! $schema->hasTable('returns')) {
            return;
        }

        DB::connection('order_fulfillment')
            ->table('returns')
            ->where('status', 'Pending')
            ->update(['status' => 'NEW']);
    }
};

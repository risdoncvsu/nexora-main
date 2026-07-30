<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These rows were placeholders created as a side effect of PO
        // creation, not supplier shipments logged by a Procurement user.
        // Do not remove anything already logged or in progress.
        DB::connection('procurement')->table('deliveries')
            ->where('shipment_number', 'like', 'SHP-PO-%')
            ->whereIn('status', ['pending', 'approved'])
            ->delete();
    }

    public function down(): void
    {
        // Placeholder deliveries cannot be reconstructed safely.
    }
};

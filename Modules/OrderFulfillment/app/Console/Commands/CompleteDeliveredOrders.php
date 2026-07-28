<?php

namespace Modules\OrderFulfillment\Console\Commands;

use Illuminate\Console\Command;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\Shipment;

class CompleteDeliveredOrders extends Command
{
    protected $signature = 'orders:complete-delivered';

    protected $description = 'Promote delivered orders and shipments to complete after one hour';

    public function handle(): int
    {
        // Scheduled commands have no employee session, so deliberately bypass
        // the client scope while processing each client's records safely.
        $shipments = Shipment::withoutGlobalScope('client')
            ->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subHour())
            ->get();

        foreach ($shipments as $shipment) {
            $shipment->update(['status' => 'COMPLETE']);
        }

        $orders = Order::withoutGlobalScope('client')
            ->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subHour())
            ->update(['status' => 'COMPLETE', 'updated_at' => now()]);

        $this->info("Promoted {$shipments->count()} shipment(s) and {$orders} order(s) to COMPLETE.");

        return self::SUCCESS;
    }
}

<?php

namespace Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\OrderFulfillment\Models\Concerns\BelongsToClient;

class ReturnItem extends Model
{
    use HasFactory, BelongsToClient;

    protected $table = 'returns';

    protected $fillable = [
        'id',
        'order_id',
        'customer_name',
        'product_name',
        'reason',
        'status',
        'resolution',
        'due_date',
        'address',
        'refund_amount'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Reasons that mean this ReturnItem exists because an admin cancelled
     * an order (OrderController::cancel / ShippingController::cancel via
     * CancelsShipmentToReturn), not because a customer requested a return.
     * Kept in sync with the identical list in ReturnController and
     * return.blade.php.
     */
    private const ADMIN_CANCEL_REASONS = [
        'Cancelled while shipping',
        'Cancelled before shipping',
    ];

    /**
     * Which Order status a return's current status should mirror onto its
     * parent Order once it's past admin review (Pending) and hasn't been
     * Declined. Most of the pipeline just reads as "a return is/was in
     * progress" (RETURNED), but Refunded gets its own distinct Order
     * status — the money has actually moved, which is worth surfacing on
     * Orders/Dashboard as more than a generic RETURNED.
     */
    private const ORDER_STATUS_FOR_RETURN_STATUS = [
        'In Transit to Warehouse' => 'RETURNED',
        'Inspecting'              => 'RETURNED',
        'Refunded'                => 'REFUNDED',
        'Completed'               => 'RETURNED',
    ];

    protected static function booted(): void
    {
        // Keep the parent Order's status mirrored to whatever this return
        // is doing — the same idea as Shipment::booted()'s mirroring of
        // shipment status onto Order. Without this, Orders/Dashboard keep
        // showing DELIVERED after the Returns tab has already moved an
        // order into a return. Fires on every save regardless of where
        // the status change comes from: the Accept button
        // (ReturnController::accept), the generic status endpoint
        // (ReturnController::updateStatus), the returns:progress-lifecycle
        // auto-promotion command, or the test panel.
        static::updated(function (ReturnItem $return): void {
            if (! $return->wasChanged('status') || ! $return->order_id) {
                return;
            }

            // Admin-initiated cancellations already flipped the Order to
            // CANCELLED at cancel time (CancelsShipmentToReturn). That's
            // correct and final — the stock physically making it back to
            // the warehouse doesn't change what the order's outcome was,
            // so there's nothing further to sync for these.
            if (in_array($return->reason, self::ADMIN_CANCEL_REASONS, true)) {
                return;
            }

            $orderStatus = self::ORDER_STATUS_FOR_RETURN_STATUS[$return->status] ?? null;

            if ($orderStatus !== null) {
                // withoutGlobalScope('client'): this also fires from the
                // returns:progress-lifecycle console command, which runs
                // with no session to derive client_id from — same
                // reasoning as Console\Commands\CompleteDeliveredOrders.
                Order::withoutGlobalScope('client')
                    ->where('id', $return->order_id)
                    ->update([
                        'status'     => $orderStatus,
                        'updated_at' => now(),
                    ]);
            }
        });
    }
}

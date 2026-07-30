<?php

namespace Modules\OrderFulfillment\Console\Commands;

use Illuminate\Console\Command;
use Modules\OrderFulfillment\Models\ReturnItem;

class ProgressReturnLifecycle extends Command
{
    protected $signature = 'returns:progress-lifecycle';

    protected $description = 'Auto-promote returns through their post-approval lifecycle: '
        . 'In Transit to Warehouse -> Inspecting (1 day) -> Refunded (1 hour) -> Completed (10 minutes)';

    /**
     * Current status => how long it sits there before moving on, what it
     * becomes next, and the resolution text that goes with the new status.
     *
     * Runs regardless of *why* a return is in one of these statuses
     * (customer-approved or admin-cancelled) — both kinds share this same
     * warehouse-side pipeline once they've reached "In Transit to
     * Warehouse".
     */
    private const TRANSITIONS = [
        'In Transit to Warehouse' => [
            'minutes'    => 60 * 24,
            'status'     => 'Inspecting',
            'resolution' => 'In Review',
        ],
        'Inspecting' => [
            'minutes'    => 60,
            'status'     => 'Refunded',
            'resolution' => 'Refund Issued',
        ],
        'Refunded' => [
            'minutes'    => 10,
            'status'     => 'Completed',
            'resolution' => 'Returned to Inventory',
        ],
    ];

    public function handle(): int
    {
        // ReturnItem is tenant-scoped via BelongsToClient's global scope,
        // which relies on session('employee_client_id'). This command
        // runs from the scheduler where there's no session, so bypass the
        // scope deliberately and process every client's due returns —
        // same reasoning as Console\Commands\CompleteDeliveredOrders.
        $promoted = 0;

        foreach (self::TRANSITIONS as $fromStatus => $rule) {
            $due = ReturnItem::withoutGlobalScope('client')
                ->where('status', $fromStatus)
                ->where('updated_at', '<=', now()->subMinutes($rule['minutes']))
                ->get();

            foreach ($due as $return) {
                // Individual saves (rather than a mass ->update()) so
                // ReturnItem::booted()'s `updated` hook fires for each row
                // and keeps the parent Order's status mirrored, the same
                // way ReturnController's accept()/updateStatus() do.
                $return->update([
                    'status'     => $rule['status'],
                    'resolution' => $rule['resolution'],
                    'updated_at' => now(),
                ]);

                $promoted++;
            }
        }

        $this->info("Promoted {$promoted} return(s) to their next lifecycle status.");

        return self::SUCCESS;
    }
}

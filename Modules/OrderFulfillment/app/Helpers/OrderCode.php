<?php

namespace Modules\OrderFulfillment\Helpers;

/**
 * Formats the short, sequential order_number column into the "ORD-001"
 * label shown to humans. orders.id (the UUID) stays the real primary/
 * foreign key everywhere in code — this is display-only.
 */
class OrderCode
{
    public static function format(int|string|null $orderNumber): string
    {
        if ($orderNumber === null || $orderNumber === '') {
            return 'ORD-???';
        }

        return 'ORD-' . str_pad((string) $orderNumber, 3, '0', STR_PAD_LEFT);
    }
}

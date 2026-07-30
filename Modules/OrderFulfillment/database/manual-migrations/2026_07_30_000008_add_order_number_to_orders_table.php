<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Adds a short, sequential order_number alongside the UUID orders.id.
     *
     * orders.id stays the real primary/foreign key everywhere in code
     * (Order/Shipment/OrderItem/ReturnItem relationships are untouched).
     * order_number exists purely so the UI has something short to show a
     * human — formatted as "ORD-001" via
     * Modules\OrderFulfillment\Helpers\OrderCode::format().
     *
     * Written to work on both PostgreSQL (production, DigitalOcean managed
     * Postgres) and MySQL/MariaDB (local XAMPP): MySQL's ALTER TABLE
     * ... MODIFY ... AUTO_INCREMENT syntax doesn't exist in Postgres, so
     * the driver-specific finalization step is branched below. Also safe
     * to re-run if an earlier attempt only got partway through (e.g. added
     * + backfilled the column but failed on the finalization step) —
     * nothing here assumes a clean starting state.
     */
    public function up(): void
    {
        $schema = Schema::connection('order_fulfillment');
        $connection = DB::connection('order_fulfillment');
        $driver = $connection->getDriverName();

        if (! $schema->hasColumn('orders', 'order_number')) {
            $schema->table('orders', function ($table): void {
                $table->unsignedInteger('order_number')->nullable()->after('id');
            });
        }

        // Backfill only rows that don't have a number yet, in creation
        // order, so re-running after a partial failure just continues
        // where it left off instead of erroring or renumbering rows that
        // already got a number on a previous attempt.
        $ids = $connection->table('orders')
            ->whereNull('order_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        $nextNumber = (int) ($connection->table('orders')->max('order_number') ?? 0) + 1;

        foreach ($ids as $id) {
            $connection->table('orders')->where('id', $id)->update(['order_number' => $nextNumber]);
            $nextNumber++;
        }

        if ($driver === 'pgsql') {
            // Postgres equivalent of AUTO_INCREMENT: a sequence the column
            // defaults to, "owned by" the column so it's dropped along
            // with it. Each statement is safe to re-run.
            $connection->statement('CREATE SEQUENCE IF NOT EXISTS orders_order_number_seq');
            $connection->statement("ALTER TABLE orders ALTER COLUMN order_number SET DEFAULT nextval('orders_order_number_seq')");
            $connection->statement('ALTER SEQUENCE orders_order_number_seq OWNED BY orders.order_number');
            $connection->statement('ALTER TABLE orders ALTER COLUMN order_number SET NOT NULL');

            $hasUniqueConstraint = (bool) $connection->selectOne(
                "SELECT 1 FROM pg_constraint WHERE conname = 'orders_order_number_unique'"
            );
            if (! $hasUniqueConstraint) {
                $connection->statement('ALTER TABLE orders ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number)');
            }

            // Point the sequence past every backfilled/existing number so
            // the next INSERT doesn't collide with one already in use.
            $connection->statement("SELECT setval('orders_order_number_seq', {$nextNumber})");
        } elseif ($driver === 'mysql') {
            $connection->statement(
                'ALTER TABLE orders MODIFY order_number INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE, AUTO_INCREMENT = ' . $nextNumber
            );
        }
        // sqlite / other drivers: the backfilled values above are enough
        // for a dev/test run — there's no portable "promote an existing
        // column to auto-increment" statement for those.
    }

    public function down(): void {}
};

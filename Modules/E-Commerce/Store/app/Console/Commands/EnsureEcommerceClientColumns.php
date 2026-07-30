<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureEcommerceClientColumns extends Command
{
    protected $signature = 'ecommerce:ensure-client-columns';

    protected $description = 'Adds client_id indexes to the dedicated ecommerce database without touching ITSM.';

    public function handle(): int
    {
        $schema = Schema::connection('ecommerce');
        $tables = [
            'accessories_headsets', 'accessories_keyboard_accessories', 'accessories_keyboards',
            'accessories_mice', 'accessories_monitors', 'accessories_mouse_pads',
            'accessories_speaker_systems', 'addresses', 'cart_items', 'carts',
            'components_chasisfan', 'components_coolers', 'components_cpus', 'components_gpus',
            'components_motherboards', 'components_pc_cases', 'components_power_supplies',
            'components_rams', 'components_storages', 'configurator_configs', 'gaminglaptops',
            'order_items', 'orders', 'payment_methods', 'prebuilt_configs', 'storefront_listings',
            'storefront_layouts', 'customer_notifications', 'chat_messages', 'users',
            'crm_customers', 'crm_tags', 'crm_customer_tags', 'crm_segments',
            'crm_customer_segments', 'crm_product_reviews', 'crm_abandoned_carts',
            'crm_communications', 'crm_coupons', 'crm_coupon_redemptions', 'crm_leads',
            'crm_communication_templates', 'crm_activity_log', 'crm_tickets',
            'crm_ticket_notes', 'crm_campaign_log', 'crm_campaign_events',
            'crm_consent_log', 'crm_admin_notifications',
        ];

        if ($schema->hasTable('users') && ! $schema->hasColumn('users', 'client_id')) {
            $schema->table('users', function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('client_id')->nullable()->index();
            });

            if ($schema->hasColumn('users', 'company_id')) {
                DB::connection('ecommerce')->table('users')
                    ->whereNull('client_id')
                    ->whereNotNull('company_id')
                    ->update(['client_id' => DB::raw('company_id')]);
            }
        }

        foreach ($tables as $table) {
            if (! $schema->hasTable($table) || $schema->hasColumn($table, 'client_id')) {
                continue;
            }

            $schema->table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedBigInteger('client_id')->nullable()->index();
            });

            $this->line("Added client_id to {$table}.");
        }

        $this->info('Ecommerce client columns are ready. Existing rows remain unassigned until explicitly migrated.');

        return self::SUCCESS;
    }
}

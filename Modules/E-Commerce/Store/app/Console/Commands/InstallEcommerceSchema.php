<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallEcommerceSchema extends Command
{
    protected $signature = 'ecommerce:install-schema';

    protected $description = 'Install the dedicated E-Commerce schema without ITSM infrastructure tables';

    public function handle(): int
    {
        $schema = Schema::connection('ecommerce');

        // A copied migration ledger can claim the base user migration ran.
        if (! $schema->hasTable('users')) {
            $baseMigration = require base_path('Modules/E-Commerce/Store/database/migrations/0001_01_01_000000_create_users_table.php');
            $baseMigration->up();
        }

        $exitCode = $this->call('migrate', [
            '--database' => 'ecommerce',
            '--path' => 'Modules/E-Commerce/Store/database/migrations',
            '--force' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        // Repair older deployed schemas whose migration ledger predates
        // client-scoped storefront orders.
        $exitCode = $this->call('ecommerce:ensure-client-columns');

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        // Safe to run on every deployment: it only creates missing layout
        // records and client storefront slugs, never replaces a saved layout.
        return $this->call('ecommerce:backfill-storefronts');
    }
}

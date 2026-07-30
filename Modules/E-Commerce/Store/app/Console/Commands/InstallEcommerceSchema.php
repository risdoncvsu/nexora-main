<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class InstallEcommerceSchema extends Command
{
    protected $signature = 'ecommerce:install-schema';

    protected $description = 'Install E-Commerce tables on the dedicated ecommerce database only';

    public function handle(): int
    {
        // Some Store migrations use Schema:: directly. Point the default
        // resolver at Ecommerce while module migrations are running.
        $defaultConnection = config('database.default');
        Config::set('database.default', 'ecommerce');

        try {
            foreach ([
                'Modules/E-Commerce/Store/database/migrations',
                'Modules/E-Commerce/CRM/database/migrations',
            ] as $path) {
                $exitCode = $this->call('migrate', [
                    '--database' => 'ecommerce',
                    '--path' => $path,
                    '--force' => true,
                ]);

                if ($exitCode !== self::SUCCESS) {
                    return $exitCode;
                }
            }
        } finally {
            Config::set('database.default', $defaultConnection);
        }

        return $this->call('ecommerce:ensure-client-columns');
    }
}

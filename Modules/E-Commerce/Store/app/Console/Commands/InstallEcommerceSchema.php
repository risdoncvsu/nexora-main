<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;

class InstallEcommerceSchema extends Command
{
    protected $signature = 'ecommerce:install-schema';

    protected $description = 'Install E-Commerce tables on the dedicated ecommerce database only';

    public function handle(): int
    {
        return $this->call('migrate', [
            '--database' => 'ecommerce',
            '--path' => 'Modules/E-Commerce/Store/database/migrations',
            '--force' => true,
        ]);
    }
}

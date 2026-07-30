<?php

namespace Modules\Procurement\Console\Commands;

use Illuminate\Console\Command;

class InstallProcurementSchema extends Command
{
    protected $signature = 'procurement:install-schema {--force : Run in production}';

    protected $description = 'Install Procurement tables on the dedicated Procurement database only';

    public function handle(): int
    {
        $exitCode = $this->call('migrate', [
            '--database' => 'procurement',
            '--path' => 'Modules/Procurement/database/manual-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        return $exitCode === self::SUCCESS
            ? $this->call('procurement:ensure-client-columns')
            : $exitCode;
    }
}

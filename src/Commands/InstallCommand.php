<?php

namespace Dcodegroup\LaravelXeroOauth\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-xero:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Laravel Xero resources';

    /**
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Laravel Xero Migrations');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-xero-oauth-migrations']);

        $this->comment('Publishing Laravel Xero Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-xero-oauth-config']);

        $this->info('Laravel Xero scaffolding installed successfully.');
    }
}
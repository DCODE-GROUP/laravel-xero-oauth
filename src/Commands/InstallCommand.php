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
    protected $description = 'Install all of the Dcode Laravel oAuth resources';

    /**
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Horizon Migrations');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-xero-oauth-migrations']);

        $this->comment('Publishing Dcode Laravel oAuth Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'laravel-xero-oauth-config']);

        $this->info('Dcode Laravel oAuth scaffolding installed successfully.');
    }
}
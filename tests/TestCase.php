<?php

namespace Dcodegroup\LaravelXeroOauth\Tests;

use Dcodegroup\LaravelXeroOauth\LaravelXeroOauthServiceProvider;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            LaravelXeroOauthServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-xero-oauth.multi_tenant_model', null);
    }

    protected function defineDatabaseMigrations()
    {
        // Load migrations from workbench
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineRoutes($router)
    {
        // Load workbench web routes
        Route::middleware('web')
            ->group(__DIR__.'/../workbench/routes/web.php');
    }
}

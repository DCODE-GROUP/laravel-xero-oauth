<?php

namespace Dcodegroup\LaravelXeroOauth\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            'Dcodegroup\LaravelXeroOauth\LaravelXeroOauthServiceProvider',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-xero-oauth.multi_tenant_model', null);
    }

    protected function defineDatabaseMigrations()
    {
        // Load migrations from workbench
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}

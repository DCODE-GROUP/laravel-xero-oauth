<?php

namespace Dcodegroup\LaravelXeroOauth\Tests;

use Workbench\App\Models\Team;

class TenantTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('laravel-xero-oauth.multi_tenant_model', Team::class);
        config()->set('laravel-xero-oauth.current_app_tenant_session_name', 'teamID');
    }
}

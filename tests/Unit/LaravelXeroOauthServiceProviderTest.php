<?php

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\XeroOrganisationExpired;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Mockery\MockInterface;
use XeroPHP\Application;

it('resolves a fake xero application when there is no saved token', function () {
    $application = app(Application::class);

    expect($application)->toBeInstanceOf(Application::class);
    expect(config('app.laravel_xero_fake_tenant'))->toBeTrue();
    expect($application->getTransport()->getConfig()['headers']['Xero-tenant-id'])->toBe('fake_tenant');
});

it('resolves a real xero application when token has a selected tenant id', function () {
    XeroToken::factory()->create([
        'access_token' => 'access_token_selected_tenant',
        'refresh_token' => 'refresh_token_selected_tenant',
        'expires' => now()->addHour()->timestamp,
        'current_tenant_id' => 'tenant_selected',
    ]);

    $this->mock(Xero::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('getTenants');
    });

    $application = app(Application::class);

    expect(config('app.laravel_xero_fake_tenant'))->toBeFalse();
    expect($application->getTransport()->getConfig()['headers']['Xero-tenant-id'])->toBe('tenant_selected');
});

it('throws when no tenant can be resolved from token or xero tenants list', function () {
    XeroToken::factory()->create([
        'access_token' => 'access_token_missing_tenant',
        'refresh_token' => 'refresh_token_missing_tenant',
        'expires' => now()->addHour()->timestamp,
        'current_tenant_id' => null,
    ]);

    $this->mock(Xero::class, function (MockInterface $mock) {
        $mock->shouldReceive('getTenants')->once()->andReturn([]);
    });

    app(Application::class);
})->throws(XeroOrganisationExpired::class);

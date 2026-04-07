<?php

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;
use Illuminate\Support\Facades\Schema;
use League\OAuth2\Client\Token\AccessToken;
use Mockery\MockInterface;

it('returns null when token table does not exist', function () {
    Schema::drop('xero_tokens');

    expect(XeroTokenService::getToken())->toBeNull();
});

it('returns null when no latest token exists', function () {
    expect(XeroTokenService::getToken())->toBeNull();
});

it('returns latest token when it has not expired', function () {
    XeroToken::factory()->create([
        'access_token' => 'still_valid_access',
        'refresh_token' => 'still_valid_refresh',
        'expires' => now()->addHour()->timestamp,
    ]);

    $token = XeroTokenService::getToken();

    expect($token)->toBeInstanceOf(AccessToken::class);
    expect($token->getToken())->toBe('still_valid_access');
});

it('refreshes and stores a new token when latest token is expired', function () {
    XeroToken::factory()->create([
        'access_token' => 'expired_access',
        'refresh_token' => 'expired_refresh',
        'expires' => now()->subHour()->timestamp,
        'current_tenant_id' => 'tenant_abc',
    ]);

    $refreshedToken = new AccessToken([
        'id_token' => 'id.refreshed.token',
        'token_type' => 'Bearer',
        'access_token' => 'new_access_token',
        'refresh_token' => 'new_refresh_token',
        'expires' => now()->addHours(2)->timestamp,
        'scope' => 'openid email profile offline_access',
    ]);

    $this->mock(Xero::class, function (MockInterface $mock) use ($refreshedToken) {
        $mock->shouldReceive('getAccessToken')
            ->once()
            ->with('refresh_token', \Mockery::on(fn (array $params) => isset($params['refresh_token']) && $params['refresh_token'] === 'expired_refresh'))
            ->andReturn($refreshedToken);
    });

    $token = XeroTokenService::getToken();

    expect($token->getToken())->toBe('new_access_token');
    expect(XeroToken::count())->toBe(2);
    expect(XeroToken::latestToken()?->current_tenant_id)->toBe('tenant_abc');
});

it('throws unauthorized exception when refreshed token format is invalid', function () {
    XeroToken::factory()->create([
        'refresh_token' => 'expired_refresh',
        'expires' => now()->subHour()->timestamp,
    ]);

    $invalidRefreshedToken = new AccessToken([
        'access_token' => 'new_access_token',
        'refresh_token' => 'new_refresh_token',
        'token_type' => 'Bearer',
        'expires' => now()->addHour()->timestamp,
        // Missing id_token and scope.
    ]);

    $this->mock(Xero::class, function (MockInterface $mock) use ($invalidRefreshedToken) {
        $mock->shouldReceive('getAccessToken')->once()->andReturn($invalidRefreshedToken);
    });

    XeroTokenService::getToken();
})->throws(UnauthorizedXero::class);


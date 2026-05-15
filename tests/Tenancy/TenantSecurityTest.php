<?php

use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedTenancyXeroException;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;

describe('Tenant Security', function () {
    beforeEach(function () {
        // Create two tenants and authenticate as one of them.
        $this->tenantId1 = 123;
        $this->tenantId2 = 456;

        // Create dummy tokens for both tenants.
        XeroToken::factory()->create([
            'tenant_id' => $this->tenantId1,
            'access_token' => 'token_for_tenant_123',
        ]);

        XeroToken::factory()->create([
            'tenant_id' => $this->tenantId2,
            'access_token' => 'token_for_tenant_456',
        ]);
    });

    it('only retrieves tokens for the authenticated tenant', function () {
        // Simulate authentication for tenantId1.
        session(['teamID' => $this->tenantId1]);

        $token = XeroTokenService::getTokenModel();
        // Assert that we only retrieve the token for tenantId1.
        expect($token)->not()->toBeNull();
        expect($token->tenant_id)->toBe($this->tenantId1);
    });

    it('can only query tokens for the authenticated tenant', function () {
            // Simulate authentication for tenantId1.
        session(['teamID' => $this->tenantId1]);
        $tokens = XeroToken::all();
        // Assert that we only retrieve the token for tenantId1.
        expect($tokens)->toHaveCount(1);
        expect($tokens->first()->tenant_id)->toBe($this->tenantId1);
    });

    it('does not retrieve tokens if no tenant is authenticated', function () {
        // Simulate no authentication.
        session()->forget('teamID');
        $tokens = XeroToken::all();
        // Assert that we do not retrieve any tokens.
        expect($tokens)->toHaveCount(0);
    })->throws(UnauthorizedTenancyXeroException::class, 'No tenant authenticated');

    it('does not retrieve tokens if session name is not set', function () {
        config(['laravel-xero-oauth.current_app_tenant_session_name' => null]);

        $tokens = XeroToken::all();
        // Assert that we do not retrieve any tokens.
        expect($tokens)->toHaveCount(0);

    })->throws(UnauthorizedTenancyXeroException::class, 'No tenant session name configured');

    afterEach(function () {
        // Clean up any tokens created during the tests.
        config(['laravel-xero-oauth.current_app_tenant_session_name' => null]);
        config(['laravel-xero-oauth.multi_tenant_model' => null]);
    });
});
<?php

use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;

it('only retrieves tokens for the authenticated tenant', function () {
    // Create two tenants and authenticate as one of them.
    $tenantId1 = 123;
    $tenantId2 = 456;

    // Create dummy tokens for both tenants.
    XeroToken::factory()->create([
        'tenant_id' => $tenantId1,
        'access_token' => 'token_for_tenant_123',
    ]);

    XeroToken::factory()->create([
        'tenant_id' => $tenantId2,
        'access_token' => 'token_for_tenant_456',
    ]);

    // Simulate authentication for tenantId1.
    session(['teamID' => $tenantId1]);

    $token = XeroTokenService::getTokenModel();
    // Assert that we only retrieve the token for tenantId1.
    expect($token)->not()->toBeNull();
    expect($token->tenant_id)->toBe($tenantId1);
});

it('can only query tokens for the authenticated tenant', function () {
    // Create two tenants and authenticate as one of them.
    $tenantId1 = 123;
    $tenantId2 = 456;

    // Create dummy tokens for both tenants.
    XeroToken::factory()->create([
        'tenant_id' => $tenantId1,
        'access_token' => 'token_for_tenant_123',
    ]); 

    XeroToken::factory()->create([
        'tenant_id' => $tenantId2,
        'access_token' => 'token_for_tenant_456',
    ]);

    // Simulate authentication for tenantId1.
    session(['teamID' => $tenantId1]);
    $tokens = XeroToken::all();
    // Assert that we only retrieve the token for tenantId1.
    expect($tokens)->toHaveCount(1);
    expect($tokens->first()->tenant_id)->toBe($tenantId1);    
});
<?php

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Support\Str;
use League\OAuth2\Client\Token\AccessToken;
use Mockery\MockInterface;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\mock;
use function Pest\Laravel\post;

// Helper function to create a test user
function createTestUser()
{
    return User::create([
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);
}

describe('GET /xero - Index Route', function () {
    it('redirects to login if user is not authenticated', function () {
        get('/xero')
            ->assertRedirect('/login');
    });

    it('displays the index view when authenticated without a token', function () {
        $user = createTestUser();

        actingAs($user);

        get('/xero')
            ->assertStatus(200)
            ->assertViewIs('xero-oauth-views::index')
            ->assertViewHas(['token', 'tenants', 'currentTenantId'])
            ->assertViewHas('token', null)
            ->assertViewHas('tenants', []);
    });

    it('displays the index view with token and tenants when authenticated with a token', function () {
        $user = createTestUser();

        // Create a XeroToken in the database
        $xeroToken = XeroToken::factory()->create();

        $tenants = [
            (object) ['tenantId' => 'tenant_123', 'tenantType' => 'ORGANISATION', 'tenantName' => 'Test Org 1'],
            (object) ['tenantId' => 'tenant_456', 'tenantType' => 'ORGANISATION', 'tenantName' => 'Test Org 2'],
        ];

        mock(Xero::class, function (MockInterface $mock) use ($tenants) {
            $mock->shouldReceive('getTenants')->andReturn($tenants);
        });

        actingAs($user);
        get('/xero')
            ->assertStatus(200)
            ->assertViewIs('xero-oauth-views::index')
            ->assertViewHas(['token', 'tenants', 'currentTenantId'])
            ->assertViewHas('token', $xeroToken)
            ->assertViewHas('tenants', $tenants);
    });

    it('displays the index view with current_tenant_id when token has a current_tenant_id', function () {
        $user = createTestUser();
        $tenantId = Str::uuid();

        // Create a XeroToken with a current_tenant_id
        $xeroToken = XeroToken::factory()->create(['current_tenant_id' => $tenantId]);

        $tenants = [
            (object) ['tenantId' => $tenantId, 'tenantType' => 'ORGANISATION', 'tenantName' => 'Active Org'],
        ];

        mock(Xero::class, function (MockInterface $mock) use ($tenants) {
            $mock->shouldReceive('getTenants')->andReturn($tenants);
        });

        actingAs($user);

        get('/xero')
            ->assertStatus(200)
            ->assertViewHas('currentTenantId', $tenantId);
    });

    it('returns empty tenants array when getTenants returns empty', function () {
        $user = createTestUser();
        XeroToken::factory()->create();

        mock(Xero::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTenants')->andReturn([]);
        });

        actingAs($user);

        get('/xero')
            ->assertStatus(200)
            ->assertViewHas('tenants', []);
    });

    it('does not call getTenants when no token exists', function () {
        $user = createTestUser();

        mock(Xero::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('getTenants');
        });

        actingAs($user);

        get('/xero')
            ->assertStatus(200)
            ->assertViewHas('tenants', []);
    });
});

describe('GET /xero/auth - Authorization Route', function () {
    it('redirects to login if user is not authenticated', function () {
        get('/xero/auth')
            ->assertRedirect('/login');
    });

    it('redirects to Xero authorization URL when authenticated', function () {
        $user = createTestUser();
        $authorizationUrl = 'https://login.xero.com/identity/connect/authorize';

        mock(Xero::class, function (MockInterface $mock) use ($authorizationUrl) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(Mockery::on(function ($param) {
                    return isset($param['scope']);
                }))
                ->andReturn($authorizationUrl);
        });

        actingAs($user);

        get('/xero/auth')
            ->assertRedirect($authorizationUrl);
    });

    it('passes scope array from configuration to authorization URL', function () {
        $user = createTestUser();
        $authorizationUrl = 'https://login.xero.com/identity/connect/authorize';
        $expectedScopes = config('laravel-xero-oauth.oauth.scopes');

        mock(Xero::class, function (MockInterface $mock) use ($authorizationUrl, $expectedScopes) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(Mockery::on(function ($param) use ($expectedScopes) {
                    return isset($param['scope']) &&
                           is_array($param['scope']) &&
                           $param['scope'][0] === $expectedScopes;
                }))
                ->andReturn($authorizationUrl);
        });

        actingAs($user);

        get('/xero/auth')
            ->assertStatus(302)
            ->assertRedirect($authorizationUrl);
    });
});

describe('GET /xero/callback - OAuth Callback Route', function () {
    it('creates a xero token from valid authorization code', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')
                ->with('authorization_code', Mockery::on(function ($param) {
                    return isset($param['code']) && $param['code'] === 'valid_code';
                }))
                ->andReturn($accessToken);
        });

        get('/xero/callback?code=valid_code')
            ->assertRedirect(route('xero.index'));

        expect(XeroToken::count())->toBe(1);
        expect(XeroToken::latest()->first()->access_token)->toBe($accessToken->getToken());
    });

    it('throws unauthorized exception when code parameter is missing', function () {
        mock(Xero::class);

        get('/xero/callback')
            ->assertStatus(500);
    });

    it('throws unauthorized exception when token format is invalid', function () {
        $invalidToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            // Missing required fields: id_token, refresh_token, scope
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($invalidToken) {
            $mock->shouldReceive('getAccessToken')
                ->andReturn($invalidToken);
        });

        get('/xero/callback?code=test_code')
            ->assertStatus(500);
    });

    it('redirects to default route when no redirect session is set', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        get('/xero/callback?code=test_code_123')
            ->assertRedirect(route('xero.index'));
    });

    it('redirects to custom url when callback_redirect_session_name is set in session', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $customUrl = 'https://example.com/custom-callback-path';
        session(['xero_callback_redirect' => $customUrl]);
        config(['laravel-xero-oauth.callback_redirect_session_name' => 'xero_callback_redirect']);

        get('/xero/callback?code=test_code_123')
            ->assertRedirect($customUrl);
    });

    it('does not get tenant_id in token data when multi_tenant_model is configured but no session name is set', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        config(['laravel-xero-oauth.multi_tenant_model' => 'App\\Models\\Tenant']);

        get('/xero/callback?code=valid_code')
            ->assertRedirect(route('xero.index'));

        expect(XeroToken::count())->toBe(0);
        // tenant_id should be null since no session is set
        expect(XeroToken::latest()->first()?->tenant_id)->toBeNull();
    });

    it('does not break when session variables are not configured', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        // Leave multi_tenant_model unconfigured - should work fine
        get('/xero/callback?code=valid_code')
            ->assertRedirect(route('xero.index'));

        expect(XeroToken::count())->toBe(1);
    });
});

describe('POST /xero/tenants/{tenantId} - Switch Tenant Route', function () {
    it('redirects to login if user is not authenticated', function () {
        $this->post('/xero/tenants/12345678-1234-1234-1234-123456789012/')
            ->assertRedirect('/login');
    });

    it('updates the current_tenant_id when authenticated user submits post', function () {
        $user = createTestUser();
        $xeroToken = XeroToken::factory()->create(['current_tenant_id' => null]);
        $newTenantId = Str::uuid();

        actingAs($user);

        post("/xero/tenants/{$newTenantId}/")
            ->assertRedirect();
        expect($xeroToken->refresh()->current_tenant_id)->toBe((string) $newTenantId);
    });

    it('updates the current_tenant_id for latest token only', function () {
        $user = createTestUser();
        $oldToken = XeroToken::factory()->create(['current_tenant_id' => 'old_tenant']);
        $latestToken = XeroToken::factory()->create(['current_tenant_id' => null]);
        $newTenantId = Str::uuid();

        actingAs($user);

        post("/xero/tenants/{$newTenantId}/")
            ->assertRedirect();

        expect($oldToken->refresh()->current_tenant_id)->toBe('old_tenant');
        expect($latestToken->refresh()->current_tenant_id)->toBe((string) $newTenantId);
    });

    it('redirects back to previous page', function () {
        $user = createTestUser();
        XeroToken::factory()->create();
        $tenantId = Str::uuid();
        $previousUrl = '/previous-page';

        actingAs($user);

        post("/xero/tenants/{$tenantId}/", headers: ['Referer' => $previousUrl])
            ->assertRedirect($previousUrl);
    });

    it('returns not found when no latest token exists', function () {
        $user = createTestUser();
        $tenantId = Str::uuid();

        actingAs($user);

        post("/xero/tenants/{$tenantId}/")
            ->assertNotFound();
    });
});

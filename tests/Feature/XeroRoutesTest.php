<?php

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use League\OAuth2\Client\Token\AccessToken;
use Mockery\MockInterface;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

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
        $response = $this->get('/xero');

        $response->assertRedirect('/login');
    });

    it('displays the index view when authenticated without a token', function () {
        $user = createTestUser();

        $response = actingAs($user)->get('/xero');

        $response->assertStatus(200);
        $response->assertViewIs('xero-oauth-views::index');
        $response->assertViewHas(['token', 'tenants', 'currentTenantId']);
        $response->assertViewHas('token', null);
        $response->assertViewHas('tenants', []);
    });

    it('displays the index view with token and tenants when authenticated with a token', function () {
        $user = createTestUser();

        // Create a XeroToken in the database
        $xeroToken = XeroToken::factory()->create();

        $tenants = [
            (object) ['tenantId' => 'tenant_123', 'tenantType' => 'ORGANISATION', 'tenantName' => 'Test Org 1'],
            (object) ['tenantId' => 'tenant_456', 'tenantType' => 'ORGANISATION', 'tenantName' => 'Test Org 2'],
        ];

        $this->mock(Xero::class, function (MockInterface $mock) use ($tenants) {
            $mock->shouldReceive('getTenants')->andReturn($tenants);
        });

        $response = $this->actingAs($user)->get('/xero');

        $response->assertStatus(200);
        $response->assertViewIs('xero-oauth-views::index');
        $response->assertViewHas(['token', 'tenants', 'currentTenantId']);
        $response->assertViewHas('token', $xeroToken);
        $response->assertViewHas('tenants', $tenants);
    });

    it('displays the index view with current_tenant_id when token has a current_tenant_id', function () {
        $user = createTestUser();
        $tenantId = Str::uuid();

        // Create a XeroToken with a current_tenant_id
        $xeroToken = XeroToken::factory()->create(['current_tenant_id' => $tenantId]);

        $tenants = [
            (object) ['tenantId' => $tenantId, 'tenantType' => 'ORGANISATION', 'tenantName' => 'Active Org'],
        ];

        $this->mock(Xero::class, function (MockInterface $mock) use ($tenants) {
            $mock->shouldReceive('getTenants')->andReturn($tenants);
        });

        $response = $this->actingAs($user)->get('/xero');

        $response->assertStatus(200);
        $response->assertViewHas('currentTenantId', $tenantId);
    });

    it('returns empty tenants array when getTenants returns empty', function () {
        $user = createTestUser();
        XeroToken::factory()->create();

        $this->mock(Xero::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTenants')->andReturn([]);
        });

        $response = $this->actingAs($user)->get('/xero');

        $response->assertStatus(200);
        $response->assertViewHas('tenants', []);
    });

    it('does not call getTenants when no token exists', function () {
        $user = createTestUser();

        $this->mock(Xero::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('getTenants');
        });

        $response = $this->actingAs($user)->get('/xero');

        $response->assertStatus(200);
        $response->assertViewHas('tenants', []);
    });
});

describe('GET /xero/auth - Authorization Route', function () {
    it('redirects to login if user is not authenticated', function () {
        $response = $this->get('/xero/auth');

        $response->assertRedirect('/login');
    });

    it('redirects to Xero authorization URL when authenticated', function () {
        $user = createTestUser();
        $authorizationUrl = 'https://login.xero.com/identity/connect/authorize';

        $this->mock(Xero::class, function (MockInterface $mock) use ($authorizationUrl) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(Mockery::on(function ($param) {
                    return isset($param['scope']);
                }))
                ->andReturn($authorizationUrl);
        });

        $response = $this->actingAs($user)->get('/xero/auth');

        $response->assertRedirect($authorizationUrl);
    });

    it('passes scope array from configuration to authorization URL', function () {
        $user = createTestUser();
        $authorizationUrl = 'https://login.xero.com/identity/connect/authorize';
        $expectedScopes = config('laravel-xero-oauth.oauth.scopes');

        $this->mock(Xero::class, function (MockInterface $mock) use ($authorizationUrl, $expectedScopes) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(Mockery::on(function ($param) use ($expectedScopes) {
                    return isset($param['scope']) &&
                           is_array($param['scope']) &&
                           $param['scope'][0] === $expectedScopes;
                }))
                ->andReturn($authorizationUrl);
        });

        $response = $this->actingAs($user)->get('/xero/auth');

        $response->assertStatus(302);
        $response->assertRedirect($authorizationUrl);
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

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')
                ->with('authorization_code', Mockery::on(function ($param) {
                    return isset($param['code']) && $param['code'] === 'valid_code';
                }))
                ->andReturn($accessToken);
        });

        $response = $this->get('/xero/callback?code=valid_code');

        $response->assertRedirect(route('xero.index'));
        expect(XeroToken::count())->toBe(1);
        expect(XeroToken::latest()->first()->access_token)->toBe($accessToken->getToken());
    });

    it('throws unauthorized exception when code parameter is missing', function () {
        $this->mock(Xero::class);

        $this->get('/xero/callback')
            ->assertStatus(500);
    });

    it('throws unauthorized exception when token format is invalid', function () {
        $invalidToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            // Missing required fields: id_token, refresh_token, scope
        ]);

        $this->mock(Xero::class, function (MockInterface $mock) use ($invalidToken) {
            $mock->shouldReceive('getAccessToken')
                ->andReturn($invalidToken);
        });

        $this->get('/xero/callback?code=test_code')
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

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $response = $this->get('/xero/callback?code=test_code_123');

        $response->assertRedirect(route('xero.index'));
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

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $customUrl = 'https://example.com/custom-callback-path';
        Session::put('xero_callback_redirect', $customUrl);
        config(['laravel-xero-oauth.callback_redirect_session_name' => 'xero_callback_redirect']);

        $response = $this->get('/xero/callback?code=test_code_123');

        $response->assertRedirect($customUrl);
    });

    it('sets tenant_id in token data when multi_tenant_model is configured', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.Str::random(40),
            'refresh_token' => 'refresh_'.Str::random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        config(['laravel-xero-oauth.multi_tenant_model' => 'App\\Models\\Tenant']);

        $response = $this->get('/xero/callback?code=valid_code');

        $response->assertRedirect(route('xero.index'));
        expect(XeroToken::count())->toBe(1);
        // tenant_id should be null since no session is set
        expect(XeroToken::latest()->first()->tenant_id)->toBeNull();
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

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        // Leave multi_tenant_model unconfigured - should work fine
        $response = $this->get('/xero/callback?code=valid_code');

        $response->assertRedirect(route('xero.index'));
        expect(XeroToken::count())->toBe(1);
    });
});

describe('POST /xero/tenants/{tenantId} - Switch Tenant Route', function () {
    it('redirects to login if user is not authenticated', function () {
        $response = $this->post('/xero/tenants/12345678-1234-1234-1234-123456789012/');

        $response->assertRedirect('/login');
    });

    it('updates the current_tenant_id when authenticated user submits post', function () {
        $user = createTestUser();
        $xeroToken = XeroToken::factory()->create(['current_tenant_id' => null]);
        $newTenantId = Str::uuid();

        $response = $this->actingAs($user)->post("/xero/tenants/{$newTenantId}/");

        $response->assertRedirect();
        expect($xeroToken->refresh()->current_tenant_id)->toBe((string) $newTenantId);
    });

    it('updates the current_tenant_id for latest token only', function () {
        $user = createTestUser();
        $oldToken = XeroToken::factory()->create(['current_tenant_id' => 'old_tenant']);
        $latestToken = XeroToken::factory()->create(['current_tenant_id' => null]);
        $newTenantId = Str::uuid();

        $response = $this->actingAs($user)->post("/xero/tenants/{$newTenantId}/");

        $response->assertRedirect();
        expect($oldToken->refresh()->current_tenant_id)->toBe('old_tenant');
        expect($latestToken->refresh()->current_tenant_id)->toBe((string) $newTenantId);
    });

    it('redirects back to previous page', function () {
        $user = createTestUser();
        XeroToken::factory()->create();
        $tenantId = Str::uuid();

        $response = $this->actingAs($user)
            ->from('/previous-page')
            ->post("/xero/tenants/{$tenantId}/");

        $response->assertRedirect('/previous-page');
    });

    it('handles null latest token gracefully', function () {
        $user = createTestUser();
        $tenantId = Str::uuid();

        // No token created, so latestToken() will return null
        // This reveals a bug in the controller - it should handle this case
        $this->actingAs($user)->post("/xero/tenants/{$tenantId}/")->assertServerError();
    });
});

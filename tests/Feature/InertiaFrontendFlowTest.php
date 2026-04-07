<?php

namespace Inertia {
    if (! class_exists('Inertia\\Inertia')) {
        class Inertia
        {
            public static function render(string $component, array $props = [])
            {
                return response()
                    ->json(['component' => $component, 'props' => $props])
                    ->header('X-Inertia-Stub', 'render');
            }

            public static function location(string $url)
            {
                return redirect()->to($url)->header('X-Inertia-Stub', 'location');
            }
        }
    }
}

namespace {
    use Calcinai\OAuth2\Client\Provider\Xero;
    use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
    use League\OAuth2\Client\Token\AccessToken;
    use Mockery\MockInterface;
    use Workbench\App\Models\User;

    use function Pest\Laravel\actingAs;

    function createInertiaFlowUser(): User
    {
        return User::create([
            'name' => 'Inertia User',
            'email' => 'inertia-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    beforeEach(function () {
        config(['laravel-xero-oauth.frontend.driver' => 'inertia']);
    });

    it('renders inertia component for xero index when inertia driver is enabled', function () {
        $user = createInertiaFlowUser();

        $response = actingAs($user)->get('/xero');

        $response->assertOk();
        $response->assertHeader('X-Inertia-Stub', 'render');
        $response->assertJsonPath('component', 'Xero/OAuth/Index');
        $response->assertJsonPath('props.authUrl', route('xero.auth'));
    });

    it('uses inertia location for xero auth endpoint', function () {
        $user = createInertiaFlowUser();
        $authorizationUrl = 'https://login.xero.com/identity/connect/authorize?example=1';

        $this->mock(Xero::class, function (MockInterface $mock) use ($authorizationUrl) {
            $mock->shouldReceive('getAuthorizationUrl')->once()->andReturn($authorizationUrl);
        });

        $response = actingAs($user)->get('/xero/auth');

        $response->assertRedirect($authorizationUrl);
        $response->assertHeader('X-Inertia-Stub', 'location');
    });

    it('uses inertia location for callback redirect endpoint', function () {
        $accessToken = new AccessToken([
            'access_token' => 'access_'.str()->random(40),
            'refresh_token' => 'refresh_'.str()->random(40),
            'token_type' => 'Bearer',
            'expires' => now()->addHours(1)->timestamp,
            'id_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature',
            'scope' => 'openid email profile offline_access',
        ]);

        $this->mock(Xero::class, function (MockInterface $mock) use ($accessToken) {
            $mock->shouldReceive('getAccessToken')->once()->andReturn($accessToken);
        });

        $response = $this->get('/xero/callback?code=inertia_code');

        $response->assertRedirect(route('xero.index'));
        $response->assertHeader('X-Inertia-Stub', 'location');
        expect(XeroToken::count())->toBe(1);
    });
}

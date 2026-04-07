<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;
use Illuminate\Routing\Controller;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use RuntimeException;

class XeroController extends Controller
{
    private Xero $xeroClient;

    /**
     * XeroController constructor.
     */
    public function __construct(Xero $xero)
    {
        $this->xeroClient = $xero;
    }

    /**
     * @throws UnauthorizedXero
     * @throws IdentityProviderException
     */
    public function __invoke()
    {
        $tenants = [];
        $token = XeroTokenService::getToken();
        $latestToken = XeroToken::latestToken();
        if ($token) {
            $tenants = $this->xeroClient->getTenants($token);
        }

        if (config('laravel-xero-oauth.frontend.driver') === 'inertia') {
            if (! class_exists(\Inertia\Inertia::class)) {
                throw new RuntimeException('Inertia frontend driver is configured but inertiajs/inertia-laravel is not installed.');
            }

            return \Inertia\Inertia::render(config('laravel-xero-oauth.frontend.inertia.component'), [
                'token' => $latestToken,
                'tenants' => $tenants,
                'currentTenantId' => $latestToken->current_tenant_id ?? null,
                'authUrl' => route(config('laravel-xero-oauth.path').'.auth'),
            ]);
        }

        return view('xero-oauth-views::index', [
            'token' => $latestToken,
            'tenants' => $tenants,
            'currentTenantId' => $latestToken->current_tenant_id ?? null,
        ]);
    }
}

<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

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
     * @return Application|Factory|View
     *
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

        return view('xero-oauth-views::index', [
            'token' => $latestToken,
            'tenants' => $tenants,
            'currentTenantId' => $latestToken->current_tenant_id ?? null,
        ]);
    }
}

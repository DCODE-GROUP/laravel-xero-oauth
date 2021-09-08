<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use App\Http\Controllers\Controller;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class XeroController extends Controller
{
    private Xero $xeroClient;

    /**
     * XeroController constructor.
     *
     * @param Xero $xero
     */
    public function __construct(Xero $xero)
    {
        $this->xeroClient = $xero;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function __invoke()
    {
        $tenants     = [];
        $token       = XeroTokenService::getToken();
        $latestToken = XeroToken::latestToken();
        if ($token) {
            $tenants = $this->xeroClient->getTenants($token);
        }

        return view('xero-oauth-views::index', [
            'token'           => $latestToken,
            'tenants'         => $tenants,
            'currentTenantId' => $latestToken->current_tenant_id ?? null
        ]);
    }
}

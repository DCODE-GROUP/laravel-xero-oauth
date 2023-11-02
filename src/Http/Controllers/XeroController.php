<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use App\Http\Controllers\Controller;
use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Dcodegroup\LaravelXeroOauth\XeroTokenService;

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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     *
     * @throws \Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
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

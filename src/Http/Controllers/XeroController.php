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
     * @return Factory|View
     * @throws AuthorizationException|IdentityProviderException
     * @throws UnauthorizedXero
     */
    public function index()
    {
        $this->authorize('index', XeroToken::class);
        $tenants     = [];
        $token       = XeroTokenService::getToken();
        $latestToken = XeroToken::latestToken();
        if ($token) {
            $tenants = $this->xeroClient->getTenants($token);
        }

        return view('admin.xero.index', [
            'token'           => $latestToken,
            'tenants'         => $tenants,
            'currentTenantId' => $latestToken->current_tenant_id ?? null
        ]);
    }

    /**
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function auth()
    {
        $this->authorize('auth', XeroToken::class);

        $authUrl = $this->xeroClient->getAuthorizationUrl([
            'scope' => [config('xero.oauth.scopes')]
        ]);

        return redirect()->to($authUrl);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws IdentityProviderException
     * @throws UnauthorizedXero
     * @throws AuthorizationException
     */
    public function callback(Request $request)
    {
        $this->authorize('auth', XeroToken::class);

        if (!$request->has('code') || empty($request->has('code'))) {
            throw new UnauthorizedXero('Could not authorize Xero!');
        }

        $token = $this->xeroClient->getAccessToken('authorization_code', [
            'code' => $request->input('code')
        ]);

        if (!XeroToken::isValidTokenFormat($token)) {
            throw new UnauthorizedXero('Token is invalid or the provided token has invalid format!');
        }

        XeroToken::create($token->jsonSerialize());

        return redirect()->route('admin.xero.index');
    }
}

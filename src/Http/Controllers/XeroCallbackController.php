<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use App\Http\Controllers\Controller;
use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class XeroCallbackController extends Controller
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
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->filled('code')) {
            throw new UnauthorizedXero('Could not authorize Xero!');
        }

        $token = $this->xeroClient->getAccessToken('authorization_code', [
            'code' => $request->input('code'),
        ]);

        if (! XeroToken::isValidTokenFormat($token)) {
            throw new UnauthorizedXero('Token is invalid or the provided token has invalid format!');
        }

        XeroToken::create($token->jsonSerialize());

        return redirect()->route('xero.index');
    }
}

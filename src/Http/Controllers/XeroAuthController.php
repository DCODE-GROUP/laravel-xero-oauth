<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use App\Http\Controllers\Controller;
use Calcinai\OAuth2\Client\Provider\Xero;
use Illuminate\Http\RedirectResponse;

class XeroAuthController extends Controller
{
    private Xero $xeroClient;

    /**
     * XeroController constructor.
     */
    public function __construct(Xero $xero)
    {
        $this->xeroClient = $xero;
    }

    public function __invoke(): RedirectResponse
    {
        return redirect()->to($this->xeroClient->getAuthorizationUrl([
            'scope' => [config('laravel-xero-oauth.oauth.scopes')],
        ]));
    }
}

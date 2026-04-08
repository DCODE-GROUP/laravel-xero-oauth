<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use Calcinai\OAuth2\Client\Provider\Xero;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

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

    public function __invoke(): Response
    {
        $authorizationUrl = $this->xeroClient->getAuthorizationUrl([
            'scope' => [config('laravel-xero-oauth.oauth.scopes')],
        ]);

        if (config('laravel-xero-oauth.frontend.driver') === 'inertia') {
            if (! class_exists(Inertia::class)) {
                throw new RuntimeException('Inertia frontend driver is configured but inertiajs/inertia-laravel is not installed.');
            }

            return Inertia::location($authorizationUrl);
        }

        return redirect()->to($authorizationUrl);
    }
}

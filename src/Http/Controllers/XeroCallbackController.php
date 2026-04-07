<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

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
     * @throws IdentityProviderException
     * @throws UnauthorizedXero
     */
    public function __invoke(Request $request): Response
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

        $data = $token->jsonSerialize();

        if (! empty(config('laravel-xero-oauth.multi_tenant_model'))) {
            $data['tenant_id'] = null;
            $sessionName = config('laravel-xero-oauth.current_app_tenant_session_name');
            if (! empty($sessionName) && Session::has($sessionName)) {
                $tenantId = Session::get($sessionName);
                $data['tenant_id'] = $tenantId;
            }
        }

        XeroToken::create($data);

        $url = route(config('laravel-xero-oauth.path').'.index');
        $sessionName = config('laravel-xero-oauth.callback_redirect_session_name');

        if (! empty($sessionName) && Session::has($sessionName)) {
            $url = Session::get($sessionName) ?? $url;
        }

        if (config('laravel-xero-oauth.frontend.driver') === 'inertia') {
            if (! class_exists(\Inertia\Inertia::class)) {
                throw new RuntimeException('Inertia frontend driver is configured but inertiajs/inertia-laravel is not installed.');
            }

            return \Inertia\Inertia::location($url);
        }

        return redirect()->to($url);
    }
}

<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class SwitchXeroTenantController extends Controller
{
    public function __invoke(string $tenantId): RedirectResponse
    {
        $token = XeroToken::latestToken();

        if (! $token) {
            abort(404);
        }

        $token->update(['current_tenant_id' => $tenantId]);

        return redirect()->back();
    }
}

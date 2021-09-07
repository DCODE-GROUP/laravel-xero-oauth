<?php

namespace Dcodegroup\LaravelXeroOauth\Http\Controllers;

use App\Http\Controllers\Controller;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class SwitchXeroTenantController extends Controller
{
    /**
     * @param string $tenantId
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function __invoke(string $tenantId)
    {
        $this->authorize('update', XeroToken::class);

        XeroToken::latestToken()->update(['current_tenant_id' => $tenantId]);

        return redirect()->back();
    }
}

<?php

use Dcodegroup\LaravelXeroOauth\Http\Controllers\SwitchXeroTenantController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroAuthController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroCallbackController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroController;

return [
    'oauth' => [
        'client_id' => env('XERO_CLIENT_ID', ''),
        'client_secret' => env('XERO_CLIENT_SECRET', ''),
        'scopes' => env('XERO_SCOPE', 'openid email profile accounting.settings accounting.transactions accounting.contacts offline_access payroll.payruns payroll.employees payroll.timesheets payroll.settings payroll.settings.read accounting.attachments'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Xero Oauth Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Laravel Xero oAuth will be accessible from. Feel free
    | to change this path to anything you like.
    |
    */

    'path' => env('LARAVEL_XERO_PATH', 'xero'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Xero oAuth Callback Redirect URL Session Name
    |--------------------------------------------------------------------------
    |
    | If you want to dynamically set the callback redirect URL, you can specify
    | a session name here. The value of that session will be used as the
    | callback redirect URL after successful authentication with Xero.
    | If this is null, then the default will be used.
    |
    */

    'callback_redirect_session_name' => null,

    /*
    |--------------------------------------------------------------------------
    | Laravel Xero oAuth Route Controllers
    |--------------------------------------------------------------------------
    |
    | Here you can specify the controllers that will be used for each route.
    | This allows you to easily swap out the default controllers with your own custom ones if needed.
    |
    */

    'route_controllers' => [
        'index' => XeroController::class,
        'auth' => XeroAuthController::class,
        'callback' => XeroCallbackController::class,
        'tenants' => SwitchXeroTenantController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Xero oAuth Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Laravel Xero oAuth route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    | ** EXCEPTION **
    | The callback route used by Xero will be excluded from this middleware
    |
    */

    'middleware' => [
        'web',
        'auth',
    ],

    'exclude_middleware_for_callback' => ['auth'],

    /*
     * --------------------------------------------------------------------------
     * Laravel Xero oAuth Layout
     * --------------------------------------------------------------------------
     *
     * The name of the base layout to wrap the pages in.
     * The exposed routes will have to know the layout of the app in order to
     * appear to look like the rest of the site. If one is not set then the internal one will be used.
     *
     */

    'app_layout' => env('LARAVEL_XERO_OAUTH_APP_LAYOUT', 'xero-oauth-views::layout'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Xero oAuth Multi Tenanted Support
    |--------------------------------------------------------------------------
    |
    | Model to use for multi-tenanted support. If null, multi-tenancy will be disabled.
    | Session name to use to get current tenant Id. If null, current tenant
    | will be set to null.
    |
    */

    'multi_tenant_model' => null,

    'current_app_tenant_session_name' => null,
];

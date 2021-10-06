<?php

return [
    'oauth' => [
        'client_id'     => env('XERO_CLIENT_ID', ''),
        'client_secret' => env('XERO_CLIENT_SECRET', ''),
        'scopes'        => env('XERO_SCOPE', 'openid email profile accounting.settings accounting.transactions accounting.contacts offline_access payroll.payruns payroll.employees payroll.timesheets payroll.settings payroll.settings.read accounting.attachments'),
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

    'middleware' => ['web', 'auth'],
    'exclude_middleware_for_callback' => ['auth'],

];

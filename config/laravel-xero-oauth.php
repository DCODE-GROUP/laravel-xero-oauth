<?php

return [
    'oauth' => [
        'client_id'     => env('XERO_CLIENT_ID', ''),
        'client_secret' => env('XERO_CLIENT_SECRET', ''),
        'scopes'        => env('XERO_SCOPE', 'openid email profile accounting.settings accounting.transactions accounting.contacts offline_access payroll.payruns payroll.employees payroll.timesheets payroll.settings'),
    ],


];

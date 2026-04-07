[![Latest Version](https://img.shields.io/github/release/dcodegroup/laravel-xero-oauth.svg?style=flat-square)](https://github.com/dcodegroup/laravel-xero-oauth/releases)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/dcodegroup/laravel-xero-oauth/Check%20&%20fix%20styling)
[![Total Downloads](https://img.shields.io/packagist/dt/dcodegroup/laravel-xero-oauth.svg?style=flat-square)](https://packagist.org/packages/dcodegroup/laravel-xero-oauth)

# Laravel Xero

This package provides the standard xero connection functionality used in most projects.

## Installation

You can install the package via composer:

```bash
composer require dcodegroup/laravel-xero-oauth
```

Then run the install command.

```bash
php artsian laravel-xero:install
```

This will publish the configuration file and the migration file.

Run the migrations

```bash
php artsian migrate
```

## Configuration

Most of configuration has been set the fair defaults. However you can review the configuration file at `config/laravel-xero-oauth.php` and adjust as needed

If you are using Inertia, you can switch the package frontend driver from Blade to Inertia:

```php
'frontend' => [
    'driver' => 'inertia',
    'inertia' => [
        'component' => 'Xero/OAuth/Index',
    ],
],
```

When `frontend.driver` is `inertia`, the package will:

- Render the index endpoint with your Inertia component
- Use Inertia location redirects for `/xero/auth` and `/xero/callback`

### Inertia Vue page contract (drop-in example)

Set your component path to match the config default:

```php
'frontend' => [
    'driver' => 'inertia',
    'inertia' => [
        'component' => 'Xero/OAuth/Index',
    ],
],
```

Create `resources/js/Pages/Xero/OAuth/Index.vue`:

```vue
<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  token: { type: Object, default: null },
  tenants: { type: Array, default: () => [] },
  currentTenantId: { type: String, default: null },
  authUrl: { type: String, required: true },
});

const connectToXero = () => {
  // Package returns an Inertia location response from /xero/auth
  window.location.href = props.authUrl;
};

const selectTenant = (tenantId) => {
  router.post(route('xero.tenant.update', { tenantId }));
};
</script>

<template>
  <section>
    <h1>Xero OAuth</h1>

    <button type="button" @click="connectToXero">Connect to Xero</button>

    <p v-if="token">Connected</p>
    <p v-else>Not connected</p>

    <ul v-if="tenants.length">
      <li v-for="tenant in tenants" :key="tenant.tenantId">
        <span>{{ tenant.tenantName }}</span>
        <button
          type="button"
          :disabled="tenant.tenantId === currentTenantId"
          @click="selectTenant(tenant.tenantId)"
        >
          {{ tenant.tenantId === currentTenantId ? 'Current' : 'Use this tenant' }}
        </button>
      </li>
    </ul>
  </section>
</template>
```

Expected props from the package index controller:

- `token`: latest stored token or `null`
- `tenants`: array of Xero tenants (`tenantId`, `tenantName`, `tenantType`, etc.)
- `currentTenantId`: currently selected tenant id or `null`
- `authUrl`: URL to start OAuth (`xero.auth`)

This example assumes Ziggy is installed and your route names are exposed to the client.

If you want to have the oauth screens appear within your sites layout ensure to configure the environment variable. eg.

`LARAVEL_XERO_OAUTH_APP_LAYOUT=layouts.admin`

## Usage

The package provides an endpoints which you can use. See the full list by running
```bash
php artsian route:list --name=xero
```

```
+--------+----------+-------------------------+--------------------+-------------------------------------------------------------------------+----------------------------------+
| Domain | Method   | URI                     | Name               | Action                                                                  | Middleware                       |
+--------+----------+-------------------------+--------------------+-------------------------------------------------------------------------+----------------------------------+
|        | GET|HEAD | xero                    | xero.index         | Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroController             | web                              |
|        |          |                         |                    |                                                                         | App\Http\Middleware\Authenticate |
|        | GET|HEAD | xero/auth               | xero.auth          | Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroAuthController         | web                              |
|        |          |                         |                    |                                                                         | App\Http\Middleware\Authenticate |
|        | GET|HEAD | xero/callback           | xero.callback      | Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroCallbackController     | web                              |
|        |          |                         |                    |                                                                         | App\Http\Middleware\Authenticate |
|        | POST     | xero/tenants/{tenantId} | xero.tenant.update | Dcodegroup\LaravelXeroOauth\Http\Controllers\SwitchXeroTenantController | web                              |
|        |          |                         |                    |                                                                         | App\Http\Middleware\Authenticate |
+--------+----------+-------------------------+--------------------+-------------------------------------------------------------------------+----------------------------------+
```

More Information

`example.com/xero` Which is where you will generate the link to authorise xero. This is by default protected auth middleware but you can modify in the configuration. This is where you want to link to in your admin and possibly a new window

`example.com/xero/callback` This is the route for which xero will redirect back to after the oauth has occurred. This is excluded from the middleware auth. You can change this list in the configuration also.

## BaseXeroService

The package has a `BaseXeroService` class located at `Dcodegroup\LaravelXeroOauth\BaseXeroService` 

So your application should have its own XeroService extend this base class as the initialisation is already done.

```php
<?php

namespace App\Services\Xero;

use Dcodegroup\LaravelXeroOauth\BaseXeroService;
use XeroPHP\Models\Accounting\Contact;

class XeroService extends BaseXeroService
{
    /**
     * @inheritDoc
     */
    public function createContact(object $data)
    {
    
        /**
         * $this->>xeroClient is inherited from the  BaseXeroService
         */
        $contact = new Contact($this->xeroClient);

        $contact->setName($data->name . ' (' . $data->code . ')')
            ->setFirstName($data->name)
            ->setContactNumber($data->code)
            ->setAccountNumber($data->code)
            ->setContactStatus(Contact::CONTACT_STATUS_ACTIVE)
            ->setEmailAddress($data->email)
            ->setTaxNumber('ABN')
            ->setDefaultCurrency('AUD');

        $contact = head($contact->save()->getElements());

        return $this->xeroClient->loadByGUID(Contact::class, $contact['ContactID']);
    }

}
```

## Runtime

You can check if the connection exists with the below code. (This will not work once `->getConfig()` is removed in Guzzle 8 https://github.com/guzzle/guzzle/issues/3114 )

```php
if (app(Application::class)->getTransport()->getConfig()['headers']['Xero-tenant-id'] != 'fake_tenant') {
        // do something
}
```
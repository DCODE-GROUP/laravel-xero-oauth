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

You can check if the connection exists with the below code

```php
if (app(Application::class)->getTransport()->getConfig()['headers']['Xero-tenant-id'] != 'fake_tenant') {
        // do something
}
```
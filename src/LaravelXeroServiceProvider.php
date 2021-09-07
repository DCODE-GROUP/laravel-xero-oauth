<?php

namespace Dcodegroup\LaravelXero;

use App\Models\XeroToken;
use App\Services\Xero\XeroService;
use Dcodegroup\LaravelXero\Contracts\XeroServiceInterface;
use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXero\Exceptions\XeroOrganisationExpired;
use Illuminate\Support\ServiceProvider;
use XeroPHP\Application;

class LaravelXeroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');
        }

        if (!class_exists('CreateXeroTokensTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                                 __DIR__ . '/../database/migrations/create_xero_tokens_table.php.stub.php' => database_path('migrations/' . $timestamp . '_create_xero_tokens_table.php'),
                             ], 'migrations');
        }

        $this->publishes([__DIR__ . '/../config/laravel-xero.php' => config_path('laravel-xero.php')], 'config');

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Xero::class, function () {
            return new Xero([
                                'clientId'     => config('xero.oauth.client_id'),
                                'clientSecret' => config('xero.oauth.client_secret'),
                                'redirectUri'  => route('xero.connect.callback'),
                            ]);
        });

        $this->app->bind(Application::class, function () {
            $client = resolve(Xero::class);

            $token = XeroService::getToken();

            if (!$token) {
                return new Application('fake_id', 'fake_tenant');
            }

            $latest = XeroToken::latestToken();
            $tenantId = $latest->current_tenant_id;

            if (is_null($latest->current_tenant_id)) {
                $tenant = head($client->getTenants($token));
                $tenantId = $tenant->tenantId;
            }

            if (!$tenantId) {
                throw new XeroOrganisationExpired('There is no configured organisation or the organisation is expired!');
            }

            return new Application($token->getToken(), $tenantId);
        });

        $this->app->bind(XeroServiceInterface::class, function () {
            return new XeroService(resolve(Application::class));
        });
    }
}
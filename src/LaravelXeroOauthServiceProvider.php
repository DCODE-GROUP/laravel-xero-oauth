<?php

namespace Dcodegroup\LaravelXeroOauth;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\XeroOrganisationExpired;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use XeroPHP\Application;

class LaravelXeroOauthServiceProvider extends ServiceProvider
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

        $this->publishes([__DIR__ . '/../config/laravel-xero-oauth.php' => config_path('laravel-xero-oauth.php')], 'config');


        $this->registerRoutes();
        $this->registerResources();
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
                                'clientId'     => config('laravel-xero-oauth.oauth.client_id'),
                                'clientSecret' => config('laravel-xero-oauth.oauth.client_secret'),
                                'redirectUri'  => route('laravel-xero-oauth.connect.callback'),
                            ]);
        });

        $this->app->bind(Application::class, function () {
            $client = resolve(Xero::class);

            $token = XeroTokenService::getToken();

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
    }


    protected function registerRoutes()
    {
        Route::group([
                         'prefix'     => config('laravel-xero-oauth.path'),
                         'middleware' => config('laravel-xero-oauth.middleware', 'web'),
                     ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/xero.php');
        });
    }
}

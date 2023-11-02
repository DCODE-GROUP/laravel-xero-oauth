<?php

namespace Dcodegroup\LaravelXeroOauth;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Commands\InstallCommand;
use Dcodegroup\LaravelXeroOauth\Exceptions\XeroOrganisationExpired;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Exception;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use XeroPHP\Application;

class LaravelXeroOauthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->offerPublishing();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerCommands();
        $this->registerResponseHandler();
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-xero-oauth.php', 'laravel-xero-oauth');

        $this->app->singleton(Xero::class, function () {
            return new Xero([
                'clientId' => config('laravel-xero-oauth.oauth.client_id'),
                'clientSecret' => config('laravel-xero-oauth.oauth.client_secret'),
                'redirectUri' => route(config('laravel-xero-oauth.path').'.callback'),
            ]);
        });

        $this->app->bind(Application::class, function () {
            $client = resolve(Xero::class);
            Config::set('app.laravel_xero_fake_tenant', false);

            try {
                $token = XeroTokenService::getToken();

                if (! $token) {
                    Config::set('app.laravel_xero_fake_tenant', true);
                    return new Application('fake_id', 'fake_tenant');
                }

                $latest = XeroToken::latestToken();
            } catch (Exception $e) {
                Config::set('app.laravel_xero_fake_tenant', true);
                return new Application('fake_id', 'fake_tenant');
            }

            $tenantId = $latest->current_tenant_id;

            if (is_null($latest->current_tenant_id)) {
                $tenant = head($client->getTenants($token));
                $tenantId = $tenant->tenantId;
            }

            if (! $tenantId) {
                throw new XeroOrganisationExpired('There is no configured organisation or the organisation is expired!');
            }

            return new Application($token->getToken(), $tenantId);
        });

        $this->app->bind(BaseXeroService::class, function () {
            return new BaseXeroService(resolve(Application::class));
        });
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Setup the resource publishing groups for Dcodegroup Xero oAuth.
     */
    protected function offerPublishing()
    {
        if (! class_exists('CreateXeroTokensTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_xero_tokens_table.stub.php' => database_path('migrations/'.$timestamp.'_create_xero_tokens_table.php'),
            ], 'laravel-xero-oauth-migrations');
        }

        $this->publishes([__DIR__.'/../config/laravel-xero-oauth.php' => config_path('laravel-xero-oauth.php')], 'laravel-xero-oauth-config');
    }

    protected function registerResources()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'xero-oauth-translations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'xero-oauth-views');
    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => config('laravel-xero-oauth.path'),
            'as' => config('laravel-xero-oauth.path').'.',
            'middleware' => config('laravel-xero-oauth.middleware', 'web'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/xero.php');
        });
    }

    /**
     * Listen to the RequestHandled event to prepare the Response.
     *
     * @return void
     */
    private function registerResponseHandler()
    {
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            if (! $event->request->ajax() &&
                (($event->response->headers->has('Content-Type') && strpos($event->response->headers->get('Content-Type'), 'html') === true)
                    || $event->request->getRequestFormat() == 'html'
                    || stripos($event->response->headers->get('Content-Disposition'), 'attachment;') === false) &&
                Str::startsWith($event->request->route()?->getName(), config('laravel-xero-oauth.path').'.')) {
                $content = $event->response->getContent();

                $head = View::make('xero-oauth-views::head')->render();

                // Try to put the js/css directly before the </head>
                $pos = strripos($content, '</head>');
                if (false !== $pos) {
                    $content = substr($content, 0, $pos).$head.substr($content, $pos);
                }

                $original = null;
                if ($event->response instanceof IlluminateResponse && $event->response->getOriginalContent()) {
                    $original = $event->response->getOriginalContent();
                }

                $event->response->setContent($content);

                // Restore original response (eg. the View or Ajax data)
                if ($original) {
                    $event->response->original = $original;
                }
            }
        });
    }
}

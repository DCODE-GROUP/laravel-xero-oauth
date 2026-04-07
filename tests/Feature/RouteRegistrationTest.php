<?php

use Illuminate\Support\Facades\Route;

it('registers the callback endpoint', function () {
    $route = Route::getRoutes()->getByName('xero.callback');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('xero/callback');
});

it('allows guests to hit callback while index is still auth-protected', function () {
    $this->get('/xero')->assertRedirect('/login');

    // Callback should bypass auth middleware and reach controller logic.
    $this->get('/xero/callback')->assertServerError();
});


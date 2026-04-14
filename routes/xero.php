<?php

use Illuminate\Support\Facades\Route;

Route::get('/', config('laravel-xero-oauth.route_controllers.index'))->name('index');
Route::get('/auth', config('laravel-xero-oauth.route_controllers.auth'))->name('auth');
Route::get('/callback', config('laravel-xero-oauth.route_controllers.callback'))
    ->withoutMiddleware(config('laravel-xero-oauth.exclude_middleware_for_callback', []))
    ->name('callback');
Route::post('/tenants/{tenantId}/', config('laravel-xero-oauth.route_controllers.tenants'))->name('tenant.update');

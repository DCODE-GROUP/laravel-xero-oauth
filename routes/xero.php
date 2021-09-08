<?php

use Dcodegroup\LaravelXeroOauth\Http\Controllers\SwitchXeroTenantController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroController;
use Illuminate\Support\Facades\Route;

Route::get('/', [XeroController::class, 'index'])->name('index');
Route::get('/auth', [XeroController::class, 'auth'])->name('auth');
Route::get('/callback', [XeroController::class, 'callback'])->name('callback')->withoutMiddleware(config('laravel-xero-oauth.exclude_middleware_for_callback', ['auth']));
Route::post('/tenants/{tenantId}/', SwitchXeroTenantController::class)->name('tenant.update');
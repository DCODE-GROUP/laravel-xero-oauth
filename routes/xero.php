<?php

use Dcodegroup\LaravelXeroOauth\Http\Controllers\SwitchXeroTenantController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroAuthController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroCallbackController;
use Dcodegroup\LaravelXeroOauth\Http\Controllers\XeroController;
use Illuminate\Support\Facades\Route;

Route::get('/', XeroController::class)->name('index');
Route::get('/auth', XeroAuthController::class)->name('auth');
Route::get('/callback', XeroCallbackController::class)->name('callback');
Route::post('/tenants/{tenantId}/', SwitchXeroTenantController::class)->name('tenant.update');

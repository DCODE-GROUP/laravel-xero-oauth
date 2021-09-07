<?php

use Illuminate\Support\Facades\Route;

Route::get('/', XeroController::class. '@index')->name('index');
Route::get('/auth', XeroController::class. '@auth')->name('auth');
Route::get('/callback', XeroController::class. '@callback')->name('callback');
Route::post('/tenants/{tenantId}/', SwitchXeroTenantController::class)->name('tenant.update');
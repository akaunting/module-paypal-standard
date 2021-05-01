<?php

use Illuminate\Support\Facades\Route;

/**
 * 'guest' middleware and 'portal/paypal-standard' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('paypal-standard', function () {
    Route::get('invoices/{invoice}/complete', 'Payment@return')->name('invoices.return');
    Route::post('invoices/{invoice}/complete', 'Payment@complete')->name('invoices.complete');
}, ['middleware' => 'guest']);

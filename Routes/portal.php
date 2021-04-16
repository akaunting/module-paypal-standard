<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/paypal-standard' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('paypal-standard', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
});

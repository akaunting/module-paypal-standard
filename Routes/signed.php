<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/**
 * 'signed' middleware and 'signed/paypal-standard' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('paypal-standard', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
    Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->withoutMiddleware(VerifyCsrfToken::class)->name('invoices.confirm');
    Route::post('invoices/{invoice}/return', 'Payment@return')->withoutMiddleware(VerifyCsrfToken::class)->name('invoices.return');
    Route::get('invoices/{invoice}/cancel', 'Payment@cancel')->name('invoices.cancel');
});

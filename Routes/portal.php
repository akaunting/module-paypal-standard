<?php

Route::group([
    'prefix' => '{company_id}/portal',
    'middleware' => 'portal',
    'namespace' => 'Modules\PaypalStandard\Http\Controllers'
], function () {
    Route::get('invoices/{invoice}/paypal-standard', 'Payment@show')->name('portal.invoices.paypal-standard.show');
});

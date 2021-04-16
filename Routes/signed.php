<?php

Route::group([
    'prefix' => '{company_id}/signed',
    'middleware' => 'signed',
    'namespace' => 'Modules\PaypalStandard\Http\Controllers'
], function () {
    Route::get('invoices/{invoice}/paypal-standard', 'Payment@show')->name('signed.invoices.paypal-standard.show');
});

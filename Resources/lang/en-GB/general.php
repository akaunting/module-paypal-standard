<?php

return [

    'name'              => 'PayPal Standard',
    'description'       => 'Enable the standard payment option of PayPal',

    'form' => [
        'email'         => 'Email',
        'mode'          => 'Mode',
        'debug'         => 'Debug',
        'transaction'   => 'Transaction',
        'customer'      => 'Show to Customer',
        'order'         => 'Order',
    ],

    'payment' => [
        'pending'       => 'Payment is pending',
        'not_added'     => 'Payment not added!',
        'processing'    => 'Thank you! Your payment is being processed. You will be notified once it is confirmed.',
    ],

    'test_mode'         => 'Warning: The payment gateway is in \'Sandbox Mode\'. Your account will not be charged.',
    //'description'       => 'Pay with PAYPAL',

];

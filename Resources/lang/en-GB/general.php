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
    ],

    'test_mode'         => 'Warning: The payment gateway is in \'Sandbox Mode\'. Your account will not be charged.',
    //'description'       => 'Pay with PAYPAL',

];

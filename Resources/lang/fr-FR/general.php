<?php

return [

    'name'              => 'Standard Paypal',
    'description'       => 'Activation de l\'option de paiement standard pour Paypal',

    'form' => [
        'email'         => 'Email',
        'mode'          => 'Mode',
        'debug'         => 'Debug',
        'transaction'   => 'Transaction',
        'customer'      => 'Montrer au client',
        'order'         => 'Commande',
    ],

    'payment' => [
        'pending'       => 'Le paiement est en attente',
        'not_added'     => 'Paiement non ajouté!',
    ],

    'test_mode'         => 'Avertissement: La passerelle de paiement est en mode \'Bac à sable\'. Votre compte ne sera pas débité.',
    //'description'       => 'Pay with PAYPAL',

];

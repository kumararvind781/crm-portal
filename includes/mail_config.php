<?php

/*
|--------------------------------------------------------------------------
| Gmail SMTP Configuration
|--------------------------------------------------------------------------
| Keep SMTP credentials here.
| Do NOT store Gmail App Password in database.
|--------------------------------------------------------------------------
*/

// return [

//     'host'       => 'smtp.gmail.com',
//     'port'       => 587,

//     'username'   => 'arvindunire@gmail.com',
//     'password'   => 'fqjkymdstdbsnyqd',

//     'encryption' => 'tls',

//     /*
//     | Sender
//     */
//     'from_email' => 'arvindunire@gmail.com',
//     'from_name'  => 'CRM Follow-up Reminder',

// ];



return [

    'host'       => 'sg2plzcpnl493864.prod.sin2.secureserver.net',
    'port'       => 465,

    'username'   => 'crm@unire.in',
    'password'   => '1$ZRrYlF=w&zda[w',

    'encryption' => 'ssl',

    'from_email' => 'crm@unire.in',
    'from_name'  => 'CRM Follow-up Reminder',

];
<?php

/*
|--------------------------------------------------------------------------
| Gmail SMTP Configuration
|--------------------------------------------------------------------------
| Keep SMTP credentials here.
| Do NOT store Gmail App Password in database.
|--------------------------------------------------------------------------
*/

return [

    'host'       => 'smtp.gmail.com',
    'port'       => 587,

    'username'   => 'arvindunire@gmail.com',
    'password'   => 'fqjkymdstdbsnyqd',

    'encryption' => 'tls',

    /*
    | Sender
    */
    'from_email' => 'arvindunire@gmail.com',
    'from_name'  => 'CRM Follow-up Reminder',

];
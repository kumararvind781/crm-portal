
<?php

if (PHP_SAPI === 'cli') {

    // LIVE SERVER - CRON
    define('BASE_URL', '/');

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'Unire_crm_portal');
    define('DB_USER', 'Unire_crm_portal');
    define('DB_PASS', '%~t6@$kDQtLfuS=S');

} elseif (($_SERVER['HTTP_HOST'] ?? '') === 'localhost') {

    // LOCAL WAMP
    define('BASE_URL', '/crm-portal/');

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'crm_portal');
    define('DB_USER', 'root');
    define('DB_PASS', '');

} else {

    // LIVE WEB
    define('BASE_URL', '/');

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'Unire_crm_portal');
    define('DB_USER', 'Unire_crm_portal');
    define('DB_PASS', '%~t6@$kDQtLfuS=S');
}

define('APP_NAME', 'CRM Portal');
<?php
session_start();

/* Base URL */
if ($_SERVER['HTTP_HOST'] == 'localhost') {

    define('BASE_URL', '/crm-portal/');

    // Local Database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'crm_portal');      // Your local DB name
    define('DB_USER', 'root');            // XAMPP default
    define('DB_PASS', '');                // Empty password

} else {

    define('BASE_URL', '/');

    // Live Database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'Unire_crm_portal');
    define('DB_USER', 'Unire_crm_portal');
    define('DB_PASS', '%~t6@$kDQtLfuS=S');

}

define('APP_NAME', 'CRM Portal');
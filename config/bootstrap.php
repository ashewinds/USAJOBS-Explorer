<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* once live, change above error reporting to:
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
*/

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/key_cred.php';
require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/search_config.php';
require_once __DIR__ . '/../includes/functions.php';
?>
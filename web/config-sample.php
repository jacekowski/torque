<?php

// MySQL Credentials
$db_host = 'localhost';
$db_user = '';     // Enter your MySQL username
$db_pass = '';     // Enter your MySQL password
$db_name = 'torque';
$db_charset = 'utf8';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// User credentials for Browser login
$auth_user = '';    //Sample: 'torque'
$auth_pass = '';    //Sample: 'open'

$auth_mode = "null"; // mysql

$show_session_length = true;

$debug=false;

$show_session_length = true;

$gmaps_api_key = "google_maps_api_key_goes_here";

$sentry_dsn = 'https://2761858a75824fbfab307bd4b8fb07e9@sentry.jacekowski.org/8';

ini_set('memory_limit', '-1');

?>
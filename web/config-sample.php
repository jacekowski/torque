<?php
$config = array();

// MySQL Credentials
$config['db']['host'] = 'localhost';
$config['db']['user'] = 'torque';     // Enter your MySQL username
$config['db']['pass'] = 'password';     // Enter your MySQL password
$config['db']['name'] = 'torque';
$config['db']['charset'] = 'utf8';
$config['db']['options'] = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
];
$config['db']['dsn'] = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$config['ldap']['host'] = 'example.com';
$config['ldap']['port'] = 389;
$config['ldap']['base'] = 'ou=people,dc=example,dc=com';
$config['debug'] = false;
$config['twig_profiling'] = false;
$config['show_flot'] = false;
$config['show_session_length'] = true;
$config['google_maps_api_key'] = 'maps_api_key_goes_here';
$config['here_maps_api_key'] = 'maps_api_key_goes_here';
$config['maps_provider'] = 'google';
$config['sentry_dsn'] = 'https://2761858a75824fbfab307bd4b8fb07e9@sentry.jacekowski.org/8';
$config['base_url'] = 'https://' . $_SERVER['SERVER_NAME'];
ini_set('memory_limit', '-1');
$config['version'] = shell_exec('svnversion -n');
?>
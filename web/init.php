<?php
/*
 * this file initialises framework, loads config file, and initialises database connection
 */

use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;

require __DIR__ . '/vendor/autoload.php';
require_once('config.php');
//initialise sentry as early as possible
Sentry\init(['dsn' => $config['sentry_dsn'], 'release' => $config['version']]);

//initalise database connection
require_once('logged_pdo.php');
try {
    $pdo = new LoggedPDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], $config['db']['options']);
} catch (PDOException $e) { //add better error handling later
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

require_once('functions.php');

session_set_cookie_params(0, dirname($_SERVER['SCRIPT_NAME']));
session_start();
$data = array();
$data['config'] = $config;

require_once('auth_functions.php');
$user = new user_management($pdo);

require_once('user_data.php');


if (isset($config['debug']) && $config['debug'] == true) {
    require_once('request_logger.php');
    $logger = new RequestLogger($pdo);
}

date_default_timezone_set('Europe/London');

$loader = new FilesystemLoader('templates');
$twig = new Environment($loader, [
    'cache' => 'templates_c',
    'autoescape' => false,
    'auto_reload' => true,
]);

if ($config['twig_profiling'] == true) {
    $profile = new Profile();
    $twig->addExtension(new ProfilerExtension($profile));
}

require_once('permalink_functions.php');
require_once('ui_functions.php');

?>
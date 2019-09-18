<?php
require __DIR__ . '/vendor/autoload.php';
require_once('config.php');

Sentry\init(['dsn' => $sentry_dsn ]);

require_once('logged_pdo.php');
require_once("functions.php");

session_set_cookie_params(0, dirname($_SERVER['SCRIPT_NAME']));
session_start();

require_once('auth_user.php');
//require_once("del_session.php");
//require_once("merge_sessions.php");

try {
    $pdo = new LoggedPDO($dsn, $db_user, $db_pass, $db_options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

if (isset($debug) && $debug == true) {
    require_once('request_logger.php');
    $logger = new RequestLogger($pdo);
}

if (isset($_GET['time'])){
    $_SESSION['time'] = $_GET['time'];
}
if (isset($_SESSION['time'])) {
    $timezone = $_SESSION['time'];
} else {
    $timezone ="";
}

date_default_timezone_set("Europe/London");

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false, //'templates_c',
    'autoescape' => false,
    'auto_reload' => false,
]);

$profile = new \Twig\Profiler\Profile();
$twig->addExtension(new \Twig\Extension\ProfilerExtension($profile));

?>
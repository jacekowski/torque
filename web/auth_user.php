<?php
require_once('auth_functions.php');

//This variable will be evaluated at the end of this file to check if a user is authenticated
$logged_in = false;

//session.cookie_path = "/torque/";

if (!isset($_SESSION['torque_logged_in'])) {
    $_SESSION['torque_logged_in'] = false;
}
$logged_in = (boolean)$_SESSION['torque_logged_in'];

if (!$logged_in) {
    if (auth_user()) {
        $logged_in = true;
    }
}

$_SESSION['torque_logged_in'] = $logged_in;

?>
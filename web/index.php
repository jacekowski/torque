<?php
// for performance benchmarking
$start = microtime(true);

require_once('init.php');
$data['logged_in'] = $user->is_authenticated();

h2push('/static/css/bootstrap.css', 'style');
h2push('/static/css/chosen.min.css', 'style');
h2push('/static/css/torque.css', 'style');
h2push('/static/css/select2.css', 'style');

h2push('/static/js/jquery.min.js', 'script');
h2push('/static/jquery-ui-1.11.2/jquery-ui.min.js', 'script');
h2push('/static/js/bootstrap.min.js', 'script');
h2push('/static/js/jquery.peity.min.js', 'script');
h2push('/static/js/chosen.jquery.min.js', 'script');
h2push('/static/js/sentry.5.5.0.bundle.min.js', 'script');
h2push('/static/js/torquehelpers.js', 'script');
h2push('/static/js/select2.full.js', 'script');

/*
 * first we need to check if user is authenticated, or does not require authentication
 * so the flow is going to be as follows
 * new user arrives on site -> check if the user has a valid permalink
 * no valid permalink means redirect to login page
*/


if (isset($_GET['authenticate']) && $_GET['authenticate'] == 'verify') { //check authentication data provided
    if (!$user->is_authenticated()) {
        if (auth_user()) {
            header('Location: '.$config['base_url'].'/');
            die();
        }
    }
}

if (isset($_GET['authenticate']) && $_GET['authenticate'] == 'login') { //display login page
        $template = $twig->load('login.twig');
        echo $template->render($data);
        die();
}

if (!isset($_GET['permalink'])) { //if no permalink
    if (!$user->is_authenticated()) {
        header('Location: '.$config['base_url'].'/?authenticate=login');
        die();
    }
} else {
    if (!check_permalink_validity($_GET['permalink']) && !$user->is_authenticated()){
        throw new Exception('invalid permalink');
    } else {
        generate_front_page();
        die();
    }
}

if (isset($_GET['export'])) {
    require_once('export.php');
    die();
}

if (isset($_GET['manager'])) {
    session_manager();
    die();
}

if (isset($_GET['ajax'])) {
    ajax_api();
    die();
}

if (isset($_GET['list'])) {
    session_list();
    die();
}

h2push('/hc/js/highcharts.js', 'script');
if ($config['show_flot'] == true) {
    h2push('/static/js/jquery.flot.js', 'script');
    h2push('/static/js/jquery.flot.axislabels.js', 'script');
    h2push('/static/js/jquery.flot.hiddengraphs.js', 'script');
    h2push('/static/js/jquery.flot.multihighlight-delta.js', 'script');
    h2push('/static/js/jquery.flot.selection.js', 'script');
    h2push('/static/js/jquery.flot.time.js', 'script');
    h2push('/static/js/jquery.flot.tooltip.min.js', 'script');
    h2push('/static/js/jquery.flot.updater.js', 'script');
    h2push('/static/js/jquery.flot.axislabels.js', 'script');
    h2push('/static/js/jquery.flot.resize.min.js', 'script');
}

generate_front_page();
$time = microtime(true) - $start;
//echo $time;
?>

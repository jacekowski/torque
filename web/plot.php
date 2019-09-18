<?php
require_once('permalink_functions.php');

$torque = new TorqueViewer($pdo);

$torque->fetch_sessions();
$torque->fetch_session(isset($_GET["id"]) ? $_GET["id"] : null);


$torque->fetch_plot_data();

$data['sessions'] = $torque->sessions;
$data['session_data'] = $torque->session;
$data['pid_names'] = $torque->pid_names;
$data['plot_data'] = $torque->plot_data;

$data['timezone'] = $timezone;

$data['show_session_length'] = $show_session_length;

$first = reset($torque->geolocs);
$last = end($torque->geolocs);

$data['start_city'] = find_nearest_city($first["lon"],$first["lat"]);
$data['end_city'] = find_nearest_city($last["lon"],$last["lat"]);
$data['permalink'] = generate_permalink($torque->session);
$data['geolocs'] = $torque->geolocs;
$data['valid_permalink'] = $valid_permalink;
$data['sql_log'] = $pdo->returnLog();

if ($logged_in == true or $valid_permalink == true) {
    $template = $twig->load('session.twig');
    $buffer = $template->render($data);
    $dumper = new \Twig\Profiler\Dumper\TextDumper();
    echo str_replace("%twig_profiling_placeholder%",$dumper->dump($profile),$buffer);
}
?>
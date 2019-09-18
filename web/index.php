<?php
require_once('init.php');
$data=array();
$data['logged_in']=$logged_in;

if (isset($_GET['upload_data'])){
    require_once('new_upload_data.php');
    die();
}
if (!isset($_GET["permalink"])) {
    if ($logged_in == false) {
        $template = $twig->load('login.twig');
        echo $template->render($data);
        die();
    }
}
if (isset($_GET['export'])){
    require_once('export.php');
    die();
}

require_once('plot.php');
?>
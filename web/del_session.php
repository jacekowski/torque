<?php
if (isset($_POST["deletesession"])) {
    $deletesession = preg_replace('/\D/', '', $_POST['deletesession']);
} elseif (isset($_GET["deletesession"])) {
    $deletesession = preg_replace('/\D/', '', $_GET['deletesession']);
}
if (isset($deletesession) && !empty($deletesession)) {
    // Connect to Database

    $query = "DELETE FROM raw_logs WHERE session=$deletesession;";
    $stmt = $pdo->query($query);
}
?>
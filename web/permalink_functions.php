<?php
$valid_permalink = false;
if (isset($_GET["permalink"])){
    $permalink = $pdo->quote($_GET["permalink"]);
    $query = 'SELECT * FROM permalink WHERE permalink_id=' . $permalink . ';';
    $stmt = $pdo->query($query);
    if ($stmt->rowCount() > 0) {
        $x = $stmt->fetch();
        $requests = json_encode(array("session" => $_SESSION, "SERVER" => $_SERVER));
        $query = "INSERT INTO `permalink_log` (`time`, `permalink_id`, `remote_addr`, `remote_details`) VALUES (".(time()).", ".$permalink.", ".$pdo->quote($_SERVER["REMOTE_ADDR"]).", ".$pdo->quote($requests).");";
        //echo $query;
        $stmt = $pdo->query($query);
        if ($x["active"] == true or $logged_in == true) {
            $selected_session_id = $x["session"];
            $variables = unserialize($x["variables"]);
            $_GET["plotdata"] = $variables["GET"]["plotdata"];
            $_SESSION['unlimited_speed'] = $variables["session"]['unlimited_speed'];
            $valid_permalink = true;
        }
        if ($x["active"] == false and $logged_in == true) { //activate permalink
            $query = "UPDATE `permalink` SET `active` = '1' WHERE `permalink`.`permalink_id` = ".$permalink.";";
            $stmt = $pdo->query($query);

        }

    }
}
if (!($valid_permalink or $logged_in)){
    die();
}

function generate_permalink($session)
{
    global $pdo;
    if (!isset($_GET["permalink"])) {
        require_once("lib/random.php");

        $permalink_s = bin2hex(random_bytes(32));
        $requests = array("session" => $_SESSION, "GET" => $_GET, "POST" => $_POST);
        $query = "INSERT INTO `permalink` (`permalink_id`, `time`, `session`, `variables`) VALUES (" . $pdo->quote($permalink_s) . ", " . (time()) . ", " . $pdo->quote($session["sid"]) . ", " . $pdo->quote(serialize($requests)) . ");";
        $stmt = $pdo->query($query);
    } else {
        $permalink_s = $_GET["permalink"];
    }
//cleanup
    $query = "DELETE FROM `permalink` WHERE `time` < " . (time() - 24 * 3600) . " AND `active` = 0 ;";
    $stmt = $pdo->query($query);
    return $permalink_s;
}

?>
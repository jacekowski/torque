<?php

function check_permalink_validity($permalink_id){
    global $pdo;
    $query = 'SELECT session, variables, active FROM permalink WHERE permalink_id=:permalink_id;';
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':permalink_id' => $permalink_id));
    if ($stmt->rowCount() > 0) {
        $x = $stmt->fetch();
        if ($x['active'] == true) {
            return true;
        }
    }
    return false;
}

function retrieve_permalink($permalink_id)
{
    global $pdo;
    global $user;
    $permalink_data = array();
    $permalink_data['valid_permalink'] = false;
    $query = 'SELECT session, variables, active FROM permalink WHERE permalink_id=:permalink_id;';
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':permalink_id' => $permalink_id));
    if ($stmt->rowCount() > 0) {
        $x = $stmt->fetch();
        $requests = serialize(array('session' => $_SESSION, 'SERVER' => $_SERVER));
        $query = 'INSERT INTO `permalink_log` (`time`, `permalink_id`, `remote_addr`, `remote_details`) VALUES (:time, :permalink_id, :remote_addr, :remote_details);'; //should i log all permalink access attempts?
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':time' => time(), ':permalink_id' => $permalink_id, ':remote_addr' => $_SERVER['REMOTE_ADDR'], ':remote_details' => $requests));
        if ($x['active'] == true or $user->is_authenticated()) {
            $permalink_data['session'] = $x['session'];
            $variables = unserialize($x['variables']);
            $permalink_data['variables'] = isset($variables['GET']['plotdata'])? $variables['GET']['plotdata'] : null;
            $permalink_data['unlimited_speed'] = $variables['session']['unlimited_speed'];
            $permalink_data['valid_permalink'] = true;
        }
        if ($x['active'] == false and $user->is_authenticated()) { //activate permalink
            $query = 'UPDATE `permalink` SET `active` = 1 WHERE `permalink_id` = :permalink_id;';
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':permalink_id' => $permalink_id));
        }
        if ($x['active'] == false and !$user->is_authenticated()) {
            throw new Exception('inactive permalink');
        }
    } else {
        throw new Exception('invalid permalink');
    }
    return $permalink_data;
}

function generate_permalink($session_metadata)
{
    global $pdo;
    if (!isset($_GET['permalink'])) {
        $permalink_s = bin2hex(random_bytes(32));
        $requests = array('session' => $_SESSION, 'GET' => $_GET, 'POST' => $_POST);
        $query = 'INSERT INTO `permalink` (`time`, `permalink_id`, `session`, `variables`) VALUES (:time, :permalink_id, :session, :variables);';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':time' => time(), ':permalink_id' => $permalink_s, ':session' => $session_metadata['sid'], ':variables' => serialize($requests)));
    } else {
        $permalink_s = $_GET['permalink'];
    }
    //cleanup old inactive permalinks - leave active permalinks.
    $query = 'DELETE FROM `permalink` WHERE `time` < (UNIX_TIMESTAMP()-24*3600) AND `active` = 0 ;';
    $pdo->query($query);
    return $permalink_s;
}

?>
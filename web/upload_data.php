<?php
require_once('init.php');

function make_key_sql_safe($key){
    return $key; //placeholder for now, will have to change it to fix SQLi
}


function get_fields($pdo)
{
// Create an array of all the existing fields in the raw_logs table
    $query = "SHOW COLUMNS FROM raw_logs";
    $stmt = $pdo->query($query);
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $table_fields["rawlog"][] = ($row['Field']);
        }
    }

// Create an array of all the existing fields in the profile table
    $query = "SHOW COLUMNS FROM profile";
    $stmt = $pdo->query($query);
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $table_fields["profile"][] = ($row['Field']);
        }
    }

// Create an array of all the existing fields in the defaultunit table
    $query = "SHOW COLUMNS FROM defaultunit";
    $stmt = $pdo->query($query);
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $table_fields["defaultunit"][] = ($row['Field']);
        }
    }

// Create an array of all the existing fields in the userunit table
    $query = "SHOW COLUMNS FROM userunit";
    $stmt = $pdo->query($query);
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $table_fields["userunit"][] = ($row['Field']);
        }
    }
    return $table_fields;
}
$table_fields = get_fields($pdo);


// Iterate over all the k* _GET arguments to check that a field exists
if (sizeof($_GET) > 0) {
    $pid_upload = false;
    $pid_keys = array();
    $pid_values = array();
    $userid_keys = array();
    $userid_values = array();
    $profile_keys = array();
    $profile_values = array();
    $userUnit_keys = array();
    $userUnit_values = array();
    $userShortName_keys = array();
    $userShortName_values = array();
    $userFullName_keys = array();
    $userFullName_values = array();
    $defaultUnit_keys = array();
    $defaultUnit_values = array();
    $table_altered = false;
    foreach ($_GET as $key => $value) {
        // We will operate on 5 data sets which are defined by 5 "submit values"
        //   0 = Data we aren't dealing with, do nothing
        //   1 = Session data; Any value higher than this requires an entry in the sessions table
        //   2 = Data; There is a column check, then the data is added to the raw table
        //   3 = Profile data; Update the profile data to the sessions table
        //   4 = Notice data; Alert/event data...I'm doing nothing with this yet
        // Keep columns starting with k
        if ($key == "session"){
            $upload_session = $pdo->quote($value);
        }
        if ($key == "time"){
            $upload_time = intval($value);
        }
        if (preg_match("/^k/", $key)) {
            $pid_keys[] = make_key_sql_safe($key);
            $pid_values[] = $pdo->quote($value);
            $submitval = 1;
            $pid_upload = true;
        } else if (in_array($key, array("v", "eml", "time", "id", "session"))) {
            $userid_keys[] = $key; // key here is sql safe
            $userid_values[] = $pdo->quote($value);
        } else if (preg_match("/^profile/", $key)) {
            $profile_keys[] = make_key_sql_safe($key);
            $profile_values[] = $pdo->quote($value);
            $submitval = 2;
        } else if (preg_match("/^userUnit/", $key)) {
            $userUnit_keys[] = make_key_sql_safe($key);
            $userUnit_values[] = $pdo->quote($value);
            $submitval = 3;
        } else if (preg_match("/^userShortName/", $key)) {
            $userUnit_keys[] = make_key_sql_safe($key);
            $userUnit_values[] = $pdo->quote($value);
            $submitval = 3;
        } else if (preg_match("/^userFullName/", $key)) {
            $userUnit_keys[] = make_key_sql_safe($key);
            $userUnit_values[] = $pdo->quote($value);
            $submitval = 3;
        } else if (preg_match("/^defaultUnit/", $key)) {
            $defaultUnit_keys[] = make_key_sql_safe($key);
            $defaultUnit_values[] = $pdo->quote($value);
            $submitval = 4;
        } else {
            $submitval = 0;
        }

        if (!in_array($key, $table_fields["rawlog"]) and $submitval == 1) {
            $sqlalter = "ALTER TABLE raw_logs ADD $key  FLOAT NOT NULL DEFAULT '0'";
            $stmt = $pdo->query($sqlalter);
            $table_altered = true;
        } else if (!in_array($key, $table_fields["profile"]) and $submitval == 2) {
            $sqlalter = "ALTER TABLE profile ADD $key VARCHAR(255)";
            $stmt = $pdo->query($sqlalter);
            $table_altered = true;
        } else if (!in_array($key, $table_fields["userunit"]) and $submitval == 3) {
            $sqlalter = "ALTER TABLE userunit ADD $key VARCHAR(255)";
            $stmt = $pdo->query($sqlalter);
            $table_altered = true;
        } else if (!in_array($key, $table_fields["defaultunit"]) and $submitval == 4) {
            $sqlalter = "ALTER TABLE defaultunit ADD $key VARCHAR(255) NOT NULL default '0'";
            $stmt = $pdo->query($sqlalter);
            $table_altered = true;
        }
    }
// not needed for now - will need it later to fix sqli
//    if ($table_altered){
//        $table_fields = get_fields($pdo);
//    }

    $query = "SELECT MinTime, MaxTime FROM profile where session=".$upload_session;
    $stmt = $pdo->query($query);

    $existing_session = false;
    if ($stmt->rowCount() > 0) {
        $existing_session = true;
        $session_row = $stmt->fetch();
        if ($session_row["MinTime"] == 0){
            $session_row["MinTime"] = $upload_time;
        }
        if ($session_row["MaxTime"] < $upload_time){
            $session_row["MaxTime"] = $upload_time;
        }
    }
    // Insert data for raw log key/value pairs
    if ((sizeof($pid_keys) === sizeof($pid_values)) && sizeof($pid_keys) > 0) {
        if ((sizeof($userid_keys) === sizeof($userid_values)) && sizeof($userid_keys) > 0) {
            for ($i = 0; $i < count($userid_keys); $i++) {
                $pid_keys[] = $userid_keys[$i];
                $pid_values[] = $userid_values[$i];
            }
        }
        $query = "REPLACE INTO raw_logs (" . implode(", ", $pid_keys) . ") VALUES (" . implode(",", $pid_values) . ")";
        $stmt = $pdo->query($query);
    } // Insert data for profile key/value pairs
    else if ((sizeof($profile_keys) === sizeof($profile_values)) && sizeof($profile_keys) > 0) {
        if ((sizeof($userid_keys) === sizeof($userid_values)) && sizeof($userid_keys) > 0) {
            for ($i = 0; $i < count($userid_keys); $i++) {
                $profile_keys[] = $userid_keys[$i];
                $profile_values[] = $userid_values[$i];
            }
        }
        $query = "REPLACE INTO profile (" . implode(", ", $profile_keys) . ") VALUES (" . implode(",", $profile_values) . ")";
        $stmt = $pdo->query($query);

    } // Insert data for userUnit key/value pairs
    else if ((sizeof($userUnit_keys) === sizeof($userUnit_values)) && sizeof($userUnit_keys) > 0) {
        if ((sizeof($userid_keys) === sizeof($userid_values)) && sizeof($userid_keys) > 0) {
            for ($i = 0; $i < count($userid_keys); $i++) {
                $userUnit_keys[] = $userid_keys[$i];
                $userUnit_values[] = $userid_values[$i];
            }
        }
        $query = "REPLACE INTO userunit (" . implode(", ", $userUnit_keys) . ") VALUES (" . implode(",", $userUnit_values) . ")";
        $stmt = $pdo->query($query);
    } // Insert data for defaultUnit key/value pairs
    else if ((sizeof($defaultUnit_keys) === sizeof($defaultUnit_values)) && sizeof($defaultUnit_keys) > 0) {
        if ((sizeof($userid_keys) === sizeof($userid_values)) && sizeof($userid_keys) > 0) {
            for ($i = 0; $i < count($userid_keys); $i++) {
                $defaultUnit_keys[] = $userid_keys[$i];
                $defaultUnit_values[] = $userid_values[$i];
            }
        }
        $query = "REPLACE INTO defaultunit (" . implode(", ", $defaultUnit_keys) . ") VALUES (" . implode(",", $defaultUnit_values) . ")";
        $stmt = $pdo->query($query);
    }
    if ($existing_session == true and $pid_upload == true) {
        $query = "UPDATE profile SET MinTime=" . $session_row["MinTime"] . ", MaxTime=" . $session_row["MaxTime"] . ", SessionSize=SessionSize+1 WHERE session=" . $upload_session . ";";
        $stmt = $pdo->query($query);
        // update total distance
        if (isset($_GET["kff1001"])) {
            $distance = $_GET["kff1001"]/3600;
            $query = "UPDATE profile SET SessionDistance=SessionDistance+".$distance." WHERE session=" . $upload_session . ";";
            $stmt = $pdo->query($query);
        }
        // update total used fuel (calculated based on AFR and air flow)
        if (isset($_GET["k10"]) && isset($_GET["kff124d"])) {
            $fuel = $_GET["k10"]/$_GET["kff124d"]/0.72;
            $query = "UPDATE profile SET SessionFuel=SessionFuel+".$fuel." WHERE session=" . $upload_session . ";";
            $stmt = $pdo->query($query);
        }
    }
}

//echo $pdo->printLog();

// Return the response required by Torque
echo "OK!";
//exit();
?>


<?php
require_once('init.php');

//start sql transaction - errors are handled crudely by throwing exception and not catching it which will cause PDO to automatically roll back any open transaction
$pdo->beginTransaction();

// Iterate over all _GET arguments
if (sizeof($_GET) > 0) {
    $pid_upload = false;
    $userUnit = array();
    $profile = array();
    $pids = array();

    foreach ($_GET as $key => $value) {
        if ($key == 'session') {
            $upload_session = $value;
        }
        if ($key == 'time') {
            $upload_time = intval($value);
        }

        if (preg_match('/^k/', $key)) {
            $pid = hexdec(str_replace('k', '', $key));
            $pids[$pid] = $value;
            $pid_upload = true;
        } else if (in_array($key, array('v', 'eml', 'time', 'id', 'session'))) {
            $userid[$key] = $value;
        } else if (preg_match('/^profile/', $key)) {
            $profile[$key] = $value;
        } else if (preg_match('/^userUnit/', $key)) {
            $pid = hexdec(str_replace('userUnit', '', $key));
            $userUnit[$pid]['Unit'] = $value;
        } else if (preg_match('/^userShortName/', $key)) {
            $pid = hexdec(str_replace('userShortName', '', $key));
            $userUnit[$pid]['userShortName'] = $value;
        } else if (preg_match('/^userFullName/', $key)) {
            $pid = hexdec(str_replace('userFullName', '', $key));
            $userUnit[$pid]['userFullName'] = $value;
        }
    }

    $query = 'SELECT MinTime, MaxTime, CityStart, CityEnd, v, eml, id FROM profile_v2 where session=:session FOR UPDATE;'; //set IX lock
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':session' => $upload_session));

    $session_uploaded = false;
    if ($stmt->rowCount() > 0) {
        $session_uploaded = true;
        $session_row = $stmt->fetch();
        if ($session_row['MinTime'] == 0) {
            $session_row['MinTime'] = $upload_time;
        }
        if ($session_row['MaxTime'] < $upload_time) {
            $session_row['MaxTime'] = $upload_time;
        }

        if ($session_row['v'] != $userid['v'] or $session_row['eml'] != $userid['eml'] or $session_row['id'] != $userid['id']) { //if session has already been uploaded compare with previously uploaded data
            throw new Exception('Invalid session data');
        }
    }

    // Insert raw pid data
    if ((sizeof($pids) > 0)) {
        $query = 'REPLACE INTO raw_logs_v2 (`session`, `time`, `pid`, `value`) VALUES (:session, :time, :pid, :value)';
        $stmt = $pdo->prepare($query);
        foreach ($pids as $key => $val) {
            $stmt->execute(array(
                ':session' => $upload_session,
                ':time' => $upload_time,
                ':pid' => $key,
                ':value' => $val
            ));
        }
    } // Insert profile data
    else if (sizeof($profile) > 0 && !$session_uploaded) {
        $query = 'INSERT INTO profile_v2 (`profileName`, `profileFuelType`, `profileWeight`, `profileVe`, `profileFuelCost`, `eml`, `v`, `session`, `id`, `time`) VALUES (:profileName, :profileFuelType, :profileWeight, :profileVe, :profileFuelCost, :eml, :v, :session, :id, :time)';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':profileName' => $profile['profileName'],
            ':profileFuelType' => $profile['profileFuelType'],
            ':profileWeight' => $profile['profileWeight'],
            ':profileVe' => $profile['profileVe'],
            ':profileFuelCost' => $profile['profileFuelCost'],
            ':eml' => $userid['eml'],
            ':v' => $userid['v'],
            ':session' => $userid['session'],
            ':id' => $userid['id'],
            ':time' => $userid['time']
        ));

    } // Insert data for userUnit key/value pairs
    else if (sizeof($userUnit) > 0) {
        $query = 'INSERT INTO userunit_v2 (`session`, `pid`, `unit`, `fullName`, `shortName`) VALUES (:session, :pid, :unit, :fullName, :shortName)';
        $stmt = $pdo->prepare($query);
        foreach ($userUnit as $key => $val) {
            $stmt->execute(array(
                ':session' => $upload_session,
                ':pid' => $key,
                ':unit' => (isset($val['Unit']) ? $val['Unit'] : ''),
                ':fullName' => (isset($val['userFullName']) ? $val['userFullName'] : ''),
                ':shortName' => (isset($val['userShortName']) ? $val['userShortName'] : '')
            ));
        }

    }
    if ($session_uploaded == true and $pid_upload == true) { // update existing session with new totals
        // update total distance
        if (isset($_GET['kff1001'])) {
            $distance = $_GET['kff1001'] / 3600;
        } else {
            $distance = 0;
        }

        // update total used fuel (calculated based on AFR and air flow)
        if (isset($_GET['k10']) && isset($_GET['kff124d'])) {
            $fuel = $_GET['k10'] / $_GET['kff124d'] / 0.72;
        } else {
            $fuel = 0;
        }

        $city_start = array();
        $city_end = array();

        $city_start['idx'] = $session_row['CityStart'];
        $city_end['idx'] = $session_row['CityEnd'];

        if (isset($_GET['kff1005']) && isset($_GET['kff1006'])) {
            if ($session_row['CityStart'] == 0) {
                $city_start = find_nearest_city($_GET['kff1005'], $_GET['kff1006']);
            } else {
                $city_end = find_nearest_city($_GET['kff1005'], $_GET['kff1006']);
            }
        }

        $query = 'UPDATE profile_v2 SET MinTime=:MinTime, MaxTime=:MaxTime, SessionSize=SessionSize+1, SessionDistance=SessionDistance+:distance, CityEnd=:city_end, CityStart=:city_start,SessionFuel=SessionFuel+:fuel  WHERE session=:session;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(
            ':session' => $upload_session,
            ':MinTime' => $session_row['MinTime'],
            ':MaxTime' => $session_row['MaxTime'],
            ':city_start' => $city_start['idx'],
            ':city_end' => $city_end['idx'],
            ':fuel' => $fuel,
            ':distance' => $distance
        ));
    }
}

//if nothing failed previously transaction is commited here.
$pdo->commit();

// Return the response required by Torque
echo 'OK!';
//exit();
?>


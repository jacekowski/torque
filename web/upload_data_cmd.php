<?php
//commandline upload script to convert to new database structure using old web server logs
$_SERVER['SERVER_NAME'] = 'torque.jacekowski.org';
require_once('init.php');
$line_count=0;
$pdo->beginTransaction();

$handle = @fopen($argv[1], 'r');
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $line = substr($buffer, 17);
        $line = str_replace('\n', '', $line);
        parse_str($line,$_GET);
        if (!isset($_GET['session'])){
            continue;
        }

        // Iterate over all the k* _GET arguments to check that a field exists
        if (sizeof($_GET) > 0) {
            $pid_upload = false;
            $pid_keys = array();
            $pid_values = array();
            $userUnit = array();
            $profile = array();

            foreach ($_GET as $key => $value) {
                if ($key == 'session'){
                    $upload_session = $value;
                }
                if ($key == 'time'){
                    $upload_time = intval($value);
                }
                // Keep columns starting with k
                if (preg_match('/^k/', $key)) {
                    $pid_keys[] = hexdec(str_replace('k','',$key));
                    $pid_values[] = $value;
                    $pid_upload = true;
                } else if (in_array($key, array('v', 'eml', 'time', 'id', 'session'))) {
                    $userid[$key] = $value; // key here is sql safe
                } else if (preg_match('/^profile/', $key)) {
                    $profile[$key] = $value;
                } else if (preg_match('/^userUnit/', $key)) {
                    $pid = hexdec(str_replace('userUnit','',$key));
                    $userUnit[$pid]['Unit'] = $value;
                } else if (preg_match('/^userShortName/', $key)) {
                    $pid = hexdec(str_replace('userShortName','',$key));
                    $userUnit[$pid]['userShortName'] = $value;
                } else if (preg_match('/^userFullName/', $key)) {
                    $pid = hexdec(str_replace('userFullName','',$key));
                    $userUnit[$pid]['userFullName'] = $value;
                }
            }

            $query = 'SELECT MinTime, MaxTime, CityStart FROM profile_v2 where session=:session;';
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(':session' => $upload_session));

            $existing_session = false;
            if ($stmt->rowCount() > 0) {
                $existing_session = true;
                $session_row = $stmt->fetch();
                if ($session_row['MinTime'] == 0){
                    $session_row['MinTime'] = $upload_time;
                }
                if ($session_row['MaxTime'] < $upload_time){
                    $session_row['MaxTime'] = $upload_time;
                }
            }


            // Insert data for raw log key/value pairs
            if ((sizeof($pid_keys) === sizeof($pid_values)) && sizeof($pid_keys) > 0) {
                $query = 'REPLACE INTO raw_logs_v2 (`session`, `time`, `pid`, `value`) VALUES (:session, :time, :pid, :value)';
                $stmt = $pdo->prepare($query);
                for ($i = 0; $i < count($pid_keys); $i++) {
                    $stmt->execute(array(':session' => $upload_session,':time' => $upload_time,':pid' => $pid_keys[$i],':value' => $pid_values[$i]));
                }
            } // Insert data for profile key/value pairs
            else if (sizeof($profile) > 0) {
                $query = 'REPLACE INTO profile_v2 (`profileName`, `profileFuelType`, `profileWeight`, `profileVe`, `profileFuelCost`, `eml`, `v`, `session`, `id`, `time`) VALUES (:profileName, :profileFuelType, :profileWeight, :profileVe, :profileFuelCost, :eml, :v, :session, :id, :time)';
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
                $query = 'REPLACE INTO userunit_v2 (`session`, `pid`, `unit`, `fullName`, `shortName`) VALUES (:session, :pid, :unit, :fullName, :shortName)';
                $stmt = $pdo->prepare($query);
                foreach ($userUnit as $key => $val){
                    $stmt->execute(array(
                        ':session' => $upload_session,
                        ':pid' => $key,
                        ':unit' => (isset($val['Unit'])?$val['Unit']: ''),
                        ':fullName' => (isset($val['userFullName'])?$val['userFullName']: ''),
                        ':shortName' => (isset($val['userShortName'])?$val['userShortName']: '')
                    ));
                }

            }
            if ($existing_session == true and $pid_upload == true) {
                $query = 'UPDATE profile_v2 SET MinTime=:MinTime, MaxTime=:MaxTime, SessionSize=SessionSize+1 WHERE session=:session;';
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':session' => $upload_session,
                    ':MinTime' => $session_row['MinTime'],
                    ':MaxTime' => $session_row['MaxTime']
                ));

                // update total distance
                if (isset($_GET['kff1001'])) {
                    $distance = $_GET['kff1001']/3600;
                    $query = 'UPDATE profile_v2 SET SessionDistance=SessionDistance+:distance WHERE session=:session;';
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(
                        ':session' => $upload_session,
                        ':distance' => $distance
                    ));
                }

                // update total used fuel (calculated based on AFR and air flow)
                if (isset($_GET['k10']) && isset($_GET['kff124d'])) {
                    $fuel = $_GET['k10']/$_GET['kff124d']/0.72;
                    $query = 'UPDATE profile_v2 SET SessionFuel=SessionFuel+:fuel WHERE session=:session;';
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(
                        ':session' => $upload_session,
                        ':fuel' => $fuel
                    ));
                }

                if (isset($_GET['kff1005']) && isset($_GET['kff1006'])) {
                    $city = find_nearest_city($_GET['kff1005'],$_GET['kff1006']);
                    if ($session_row['CityStart'] == 0) {
                        $query = 'UPDATE profile_v2 SET CityStart=:city WHERE session=:session;';
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(array(
                            ':session' => $upload_session,
                            ':city' => $city['idx']
                        ));
                    } else {
                        $query = 'UPDATE profile_v2 SET CityEnd=:city WHERE session=:session;';
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(array(
                            ':session' => $upload_session,
                            ':city' => $city['idx']
                        ));
                    }
                }
            }
        }

        $pdo->clearLog();
        $line_count++;
        if ($line_count%1000 == 0){
            echo time()." ".$line_count." OK!\n";
            $pdo->commit();
            $pdo->beginTransaction();
        }
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}
$pdo->commit();
?>
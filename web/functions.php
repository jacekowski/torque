<?php
class TorqueViewer {

    protected $pdo;
    public $sessions;
    public $session;
    function __construct($pdo) {
        $this->pdo = $pdo;
    }
    function fetch_sessions()
    {
        //$query = "SELECT COUNT(*) as `SessionSize`, MIN(time) as `MinTime`, MAX(time) as `MaxTime`, session FROM $db_table GROUP BY session ORDER BY MIN(time) DESC";
        //$stmt = $pdo->query($query);

        $query = "SELECT session, MaxTime, MinTime, SessionDistance, SessionFuel, profileName FROM profile ORDER BY session DESC";
        $stmt = $this->pdo->query($query);

        $sessions = array();

        while ($row = $stmt->fetch()) {
            //$session_size = $row["SessionSize"];
            $sid = $row["session"];
            $sessions[$sid]["sid"] = $row["session"];
            $sessions[$sid]["date"] = date("d/m/Y H:i", $row["MinTime"]/1000);
            $sessions[$sid]["length"] = gmdate("H:i:s", ($row["MaxTime"] - $row["MinTime"]) / 1000);
            $sessions[$sid]["distance_miles"] = round(convertSpeed($row["SessionDistance"], false), 2);
            $sessions[$sid]["distance"] = round(convertSpeed($row["SessionDistance"], false), 2);
            $sessions[$sid]["distance_km"] = round($row["SessionDistance"], 2);
            $sessions[$sid]["fuel"] = round($row["SessionFuel"] / 1000, 2);
            if ($sessions[$sid]["fuel"] > 0 && $sessions[$sid]["distance_km"] > 0) {
                $sessions[$sid]["economy_mpg"] = round($sessions[$sid]["distance_miles"] / ($sessions[$sid]["fuel"] / 4.546), 2);
                $sessions[$sid]["economy_lp100"] = round($sessions[$sid]["fuel"] / $sessions[$sid]["distance_km"] * 100, 2);
            } else {
                $sessions[$sid]["economy_mpg"] = 0;
                $sessions[$sid]["economy_lp100"] = 0;
            }
            $sessions[$sid]["model"] = $row["profileName"];

        }
        $this->sessions = $sessions;
    }
    private $selected_session_id;
    public $pid_names;
    function fetch_session($sesssion_id = null){
        if (array_key_exists($sesssion_id,$this->sessions)) {
            $this->selected_session_id = $sesssion_id;
        } else {
            $this->selected_session_id = key($this->sessions);
        }
        $this->session = $this->sessions[$this->selected_session_id];

        // Create array of column name/comments for chart data selector form
        $query = 'SELECT * FROM userunit WHERE session=' . $this->session["sid"] . ';';
        $stmt = $this->pdo->query($query);
        $x = $stmt->fetch();

        foreach ($x as $key => $value) {
            if (preg_match("/^userUnit/", $key)) {
                $pid = str_replace("userUnit","",$key);
                if ($pid[0] == "0"){
                    $pid_short = substr($pid,1);
                } else {
                    $pid_short = $pid;
                }

                if ($x["userFullName".$pid] != "") {
                    $pid_names["k" . $pid_short] = $x["userFullName" . $pid]." (".$x["userUnit" . $pid].")";
                }
            }
        }
        $this->pid_names = $pid_names;

    }

    public $geolocs;
    public $plot_data;
    function fetch_plot_data()
    {
        $plot_data = array();


        // The columns to plot -- if no PIDs are specified I default to speed
        if (!isset($_GET["plotdata"][0])) {
            $_GET["plotdata"][0] = 'kff1001';
        }

        $to_plot = "";

        foreach ($_GET["plotdata"] as $plotpid) {
            if ($plotpid[1] == "0") {
                $plotpid = "k" . substr($plotpid, 2);
            }
            $plot_data[$plotpid] = array("pid" => $plotpid);
            $to_plot .= "," . $plotpid;
        }

        $to_plot .= ", kff1006"; //always select latitude and longitude
        $to_plot .= ", kff1005";

        $query = 'SELECT time' . $to_plot . ' FROM raw_logs WHERE session=' . $this->session["sid"] . ' ORDER BY time ASC;';
        $stmt = $this->pdo->query($query);

        $geolocs = array();

        //fetch all data points
        $keys = array_keys($plot_data);
        while ($row = $stmt->fetch()) {
            $geolocs[] = array("lat" => $row["kff1006"], "lon" => $row["kff1005"]);

            foreach ($keys as $key) {
                if (in_array($plot_data[$key]['pid'], array("kff1001", "kd"))) {
                    if ($row[$plot_data[$key]['pid']] == 255) {
                        $row[$plot_data[$key]['pid']] = 0;
                    }
                    $plot_data[$key]['data'][$row['time']] = convertSpeed($row[$plot_data[$key]['pid']], false); //convert speed to mph
                } else {
                    $plot_data[$key]['data'][$row['time']] = $row[$plot_data[$key]['pid']];
                }
            }
        }
        foreach ($keys as $key) {
            $plot_data[$key]['max'] = round(max($plot_data[$key]['data']), 1);
            $plot_data[$key]['min'] = round(min($plot_data[$key]['data']), 1);
            $plot_data[$key]['avg'] = round(average($plot_data[$key]['data']), 1);
            $plot_data[$key]['name'] = $this->pid_names[$plot_data[$key]['pid']];
        }
        $this->plot_data = $plot_data;
        $this->geolocs = $geolocs;
    }
}

function convertTemp($temperatureval, $celsius = True)
{
    if ($celsius == False) {
        $newtemp = floatval($temperatureval) * 9 / 5 + 32;
        return $newtemp;
    } else {
        $newtemp = floatval(floatval($temperatureval) - 32) * 5 / 9;
        return $newtemp;
    }
}

function convertSpeed($speedval, $kph = True)
{
    if ($kph == False) {
        $newspeed = $speedval * 0.621371;
        return $newspeed;
    } else {
        $newspeed = $speedval * 1.60934;
        return $newspeed;
    }
}

// Calculate average
function average($arr)
{
    if (!count($arr)) return 0;
    return array_sum($arr) / count($arr);
}

function find_nearest_city($lon, $lat){
    global $pdo;
    $search_degrees=0.2;
    $query = "SELECT *,ST_Distance_Sphere(`GeoLoc`, Point(".$lon.",".$lat.")) as distance FROM `cities` where Longitude > ".($lon-$search_degrees)." and Longitude < ".($lon+$search_degrees)." and Latitude > ".($lat-$search_degrees)." and Latitude < ".($lat+$search_degrees)." and ST_Distance_Sphere(`GeoLoc`, Point(".($lon).",".($lat).")) < 10000 ORDER BY `distance` LIMIT 1";
    $stmt = $pdo->query($query);
    if ($stmt != false){
        $row = $stmt->fetch();
        return $row["AccentCity"];
    }
}

?>
<?php

class TorqueViewer
{
    public $sessions;
    public $session_data = array();
    protected $pdo;
    private $selected_session_id;
    private $session_data_loaded = false;
    private $pids;

    function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    //this fetches all session (used on first page load of ajax version and all reloads of normal version)
    function fetch_sessions()
    {
        $query = 'SELECT session, MaxTime, MinTime, SessionDistance, SessionSize, SessionFuel, profileName, start_cities.AccentCity AS StartCity, end_cities.AccentCity AS EndCity  FROM profile_v2 INNER JOIN cities AS start_cities ON profile_v2.CityStart = start_cities.idx INNER JOIN cities AS end_cities on profile_v2.CityEnd = end_cities.idx ORDER BY session DESC;';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $sid = $row['session'];
            $this->sessions[$sid] = $this->parse_session_row($row);
        }
        $this->session_data_loaded = true;
    }


    function parse_session_row($row)
    {
        $session['sid'] = $row['session'];
        $session['MaxTime'] = $row['MaxTime'];
        $session['MinTime'] = $row['MinTime'];
        $session['StartCity'] = $row['StartCity'];
        $session['EndCity'] = $row['EndCity'];
        $session['SessionSize'] = $row['SessionSize'];
        $session['SessionDistance'] = $row['SessionDistance'];
        $session['SessionFuel'] = $row['SessionFuel'];
        $session['date'] = date('d/m/Y H:i', $row['MinTime'] / 1000);
        $session['length'] = gmdate('H:i:s', ($row['MaxTime'] - $row['MinTime']) / 1000);
        $session['distance_miles'] = round(convertSpeed($row['SessionDistance'], false), 2);
        $session['distance'] = round(convertSpeed($row['SessionDistance'], false), 2);
        $session['distance_km'] = round($row['SessionDistance'], 2);
        $session['fuel'] = round($row['SessionFuel'] / 1000, 2);
        if ($session['fuel'] > 0 && $session['distance_km'] > 0) {
            $session['economy_mpg'] = round($session['distance_miles'] / ($session['fuel'] / 4.546), 2);
            $session['economy_lp100'] = round($session['fuel'] / $session['distance_km'] * 100, 2);
        } else {
            $session['economy_mpg'] = 0;
            $session['economy_lp100'] = 0;
        }
        $session['model'] = $row['profileName'];

        return $session;
    }

    function fetch_session($session_id = null)
    { //3 possible outcomes, session provided and valid - load session, session provided and invalid - throw exception, null - load latest session
        if ($this->session_data_loaded) {
            if (in_array($session_id, array_keys($this->sessions))) {
                $this->selected_session_id = $session_id;
            } else {
                $this->selected_session_id = key($this->sessions);
            }
        } else {
            if ($session_id == null) {
                $query = 'SELECT session, MaxTime, MinTime, SessionDistance, SessionSize, SessionFuel, profileName, start_cities.AccentCity AS StartCity, end_cities.AccentCity AS EndCity  FROM profile_v2 INNER JOIN cities AS start_cities ON profile_v2.CityStart = start_cities.idx INNER JOIN cities AS end_cities on profile_v2.CityEnd = end_cities.idx ORDER BY session DESC LIMIT 1;';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
                $row = $stmt->fetch();
                $sid = $row['session'];
                $this->sessions[$sid] = $this->parse_session_row($row);
                $this->selected_session_id = $sid;
            } else {
                $query = 'SELECT session, MaxTime, MinTime, SessionDistance, SessionSize, SessionFuel, profileName, start_cities.AccentCity AS StartCity, end_cities.AccentCity AS EndCity  FROM profile_v2 INNER JOIN cities AS start_cities ON profile_v2.CityStart = start_cities.idx INNER JOIN cities AS end_cities on profile_v2.CityEnd = end_cities.idx WHERE session=:session_id ORDER BY session DESC;';
                $stmt = $this->pdo->prepare($query);
                $stmt->execute(array(':session_id' => $session_id));
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch();
                    $sid = $row['session'];
                    $this->sessions[$sid] = $this->parse_session_row($row);
                    $this->selected_session_id = $sid;
                } else {
                    throw new InvalidArgumentException('Session not found');
                }
            }
        }

        $this->session_data['metadata'] = $this->sessions[$this->selected_session_id];
        // Create array of column name/comments for chart data selector form
        $query = 'SELECT pid, fullName, unit FROM userunit_v2 WHERE session=:session_id;';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(array(':session_id' => $this->session_data['metadata']['sid']));

        while ($row = $stmt->fetch()) {
            if ($row['pid'] == 13 or $row['pid'] == 16715777) {//hack to change units to mph //TODO: only change when user settings want mph
                $row['unit'] = 'mph';
            }
            $this->session_data['pid_names'][$row['pid']]['name'] = $row['fullName'] . ' (' . $row['unit'] . ')';
            $this->session_data['available_pids'][]=$row['pid'];
        }
    }

    function select_pids($plot_config){
        $pids = array(16715777, 16715781, 16715782); //default pids - always pulled from database to display path
        $this->session_data['actual_variables'] = array();

        // The columns to plot -- if no PIDs are specified default to speed
        if (!isset($plot_config[0])) {
            $plot_config[0] = 16715777;
        }

        foreach ($plot_config as $plotpid) {
            if (ctype_alnum($plotpid) && is_string($plotpid)) {
                if ($plotpid[0] == 'k') {
                    $pid = hexdec(str_replace('k', '', $plotpid));
                } else {
                    $pid = intval($plotpid);
                }
            } else {
                $pid = intval($plotpid);
            }

            if (isset($this->session_data['pid_names'][$pid])) {
                $pids[] = $pid;
                $this->session_data['actual_variables'][$pid] = $pid;
                $this->session_data['plot_data'][$pid] = array('pid' => $pid, 'display' => true);
            }
        }

        $this->pids = array_unique($pids);

    }

    function select_all_pids($plot_config){
        $this->session_data['actual_variables'] = array();

        foreach ($this->session_data['available_pids'] as $pid) {
            if (isset($this->session_data['pid_names'][$pid])) {
                $pids[] = $pid;
                $this->session_data['actual_variables'][$pid] = $pid;
                $this->session_data['plot_data'][$pid] = array('pid' => $pid, 'display' => true);
            }
        }
        $this->pids = array_unique($pids);
    }

    function fetch_session_data()
    {
        global $config;

        $query = 'SELECT time, value FROM raw_logs_v2 WHERE session=:session_id AND pid=:pid ORDER BY time ASC;';
        $stmt = $this->pdo->prepare($query);

        foreach ($this->pids as $pid) {

            $stmt->execute(array(':session_id' => $this->session_data['metadata']['sid'], ':pid' => $pid));
            $rows = $stmt->fetchAll();

            //fetch all data points
            foreach ($rows as $row) {
                if ($pid == 13 or $pid == 16715777) { //correct speed errors
                    if ($row['value'] == 255) {
                        $row['value'] = 0;
                    }
                    $row['value'] = convertSpeed($row['value'], false); //convert speed to mph //TODO: only change when user settings want mph
                }
                $this->session_data['plot_data'][$pid]['data'][$row['time']] = $row['value'];
            }
            $stmt->nextRowSet();
            $stmt->closeCursor();
        }

        $keys = array_keys($this->session_data['plot_data']);
        foreach ($keys as $key) {
            $this->session_data['plot_data'][$key]['max'] = max($this->session_data['plot_data'][$key]['data']);
            $this->session_data['plot_data'][$key]['min'] = min($this->session_data['plot_data'][$key]['data']);
            $this->session_data['plot_data'][$key]['avg'] = average($this->session_data['plot_data'][$key]['data']);
            $this->session_data['plot_data'][$key]['name'] = $this->session_data['pid_names'][$key]['name'];
        }

    }

    function create_geopath()
    {
        $keys = array_keys($this->session_data['plot_data'][16715777]['data']);
        $geopath = array();

        //set bounds - used by google maps to display correct part of the map
        $bounds = array();
        $bounds['min']['lon'] = $this->session_data['plot_data'][16715781]['min'];
        $bounds['min']['lat'] = $this->session_data['plot_data'][16715782]['min'];
        $bounds['max']['lon'] = $this->session_data['plot_data'][16715781]['max'];
        $bounds['max']['lat'] = $this->session_data['plot_data'][16715782]['max'];

        if ($this->session_data['plot_data'][16715777]['max'] == 0) {
            $max_speed = 1;
        } else {
            $max_speed = $this->session_data['plot_data'][16715777]['max'];
        }

        $size = sizeof($keys) - 1;

        for ($i = 0; $i < $size; $i++) {
            $colour = round(round($this->session_data['plot_data'][16715777]['data'][$keys[$i]], 0) * 511 / $max_speed) + 1;

            if ($colour <= 256) {
                $html_colour['r'] = dechex($colour - 1);
                if (strlen($html_colour['r']) == 1) $html_colour['r'] = '0' . $html_colour['r'];
                $html_colour['g'] = 'ff';
                $html_colour['b'] = '00';
            } else {
                $html_colour['r'] = 'ff';
                $html_colour['g'] = dechex(512 - $colour);
                if (strlen($html_colour['g']) == 1) $html_colour['g'] = '0' . $html_colour['g'];
                $html_colour['b'] = '00';
            }
            $geopath[] = array('colour' => '#' . $html_colour['r'] . $html_colour['g'] . $html_colour['b'],
                'lat1' => $this->session_data['plot_data'][16715782]['data'][$keys[$i]],
                'lon1' => $this->session_data['plot_data'][16715781]['data'][$keys[$i]],
                'lat2' => $this->session_data['plot_data'][16715782]['data'][$keys[$i + 1]],
                'lon2' => $this->session_data['plot_data'][16715781]['data'][$keys[$i + 1]]);
        }
        $geopath[] = end($geopath);

        $first = reset($geopath);
        $last = end($geopath);
        $this->session_data['start_city'] = find_nearest_city($first['lon1'], $first['lat1']);
        $this->session_data['end_city'] = find_nearest_city($last['lon2'], $last['lat2']);

        $this->session_data['geopath'] = $geopath;
        $this->session_data['bounds'] = $bounds;
    }

}


function convertTemp($temperatureval, $celsius = True)
{
    if ($celsius == false) {
        $newtemp = floatval($temperatureval) * 9 / 5 + 32;
        return $newtemp;
    } else {
        $newtemp = floatval(floatval($temperatureval) - 32) * 5 / 9;
        return $newtemp;
    }
}

function convertSpeed($speedval, $kph = True)
{
    if ($kph == false) {
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

function find_nearest_city($lon, $lat)
{
    global $pdo;
    static $get_city_stmt;
    $search_degrees = 0.2;
    // check here still exists just in case i have to go back to old SQL query rather than calling function and using PDO to stop SQLi
    if (!is_numeric($lon)) {
        throw new UnexpectedValueException('Something went wrong Longitude is not numeric: ' . $lon);
    }
    if (!is_numeric($lat)) {
        throw new UnexpectedValueException('Something went wrong Latitude is not numeric: ' . $lat);
    }
//    $query = 'SELECT *,ST_Distance_Sphere(`GeoLoc`, Point('.$lon.','.$lat.')) as distance FROM `cities` where Longitude > '.($lon-$search_degrees).' and Longitude < '.($lon+$search_degrees).' and Latitude > '.($lat-$search_degrees).' and Latitude < '.($lat+$search_degrees).' and ST_Distance_Sphere(`GeoLoc`, Point('.($lon).','.($lat).')) < 10000 ORDER BY `distance` LIMIT 1';
//    $stmt = $pdo->query($query);

    if (!isset($get_city_stmt)) {
        $query = 'CALL GetCity(:lon,:lat,:search_degrees);';
        $get_city_stmt = $pdo->prepare($query);
    }
    $get_city_stmt->execute(array(':lon' => $lon, ':lat' => $lat, ':search_degrees' => $search_degrees));
    if ($get_city_stmt != false) {
        $row = $get_city_stmt->fetch();
        $get_city_stmt->nextRowSet();
        $get_city_stmt->closeCursor();
        return $row;
    }

    return false;
}

function GetCallingMethodName()
{
    $e = new Exception();
    $trace = $e->getTrace();
    //position 0 would be the line that called this function so we ignore it
    $last_call = $trace[1];
    return $last_call;
}

/**
 * @param $uri string URL to push
 * @param $as string push type
 */
function h2push($uri, $as)
{
    header('Link: <' . $uri . '>; rel=preload; as=' . $as, false);
}


?>
<?php
$torque = new TorqueViewer($pdo);

$torque->fetch_sessions();
$torque->fetch_session(isset($_GET['id']) ? $_GET['id'] : null);

$session_id = $torque->session_data['metadata']['sid'];
// Get data for session
$output = '';

$query = 'SELECT fullName, pid FROM userunit_v2 WHERE session=:session_id;';
$stmt = $pdo->prepare($query);
$stmt->execute(array(':session_id' => $session_id));
$pids = array();
if ($stmt != false) {
    $pids = $stmt->fetchAll();
} else {
    die();
}


$query = 'SELECT v, eml, session, id FROM profile_v2 WHERE session=:session_id ORDER BY time DESC;';
$stmt = $pdo->prepare($query);
$stmt->execute(array(':session_id' => $session_id));
$session_data = array();
if ($stmt != false) {
    $session_data = $stmt->fetch();
} else {
    die();
}


$query = 'SELECT time, pid, value FROM raw_logs_v2 WHERE session=:session_id ORDER BY time ASC;';
$stmt = $pdo->prepare($query);
$stmt->execute(array(':session_id' => $session_id));
$export_data = array();
while ($row = $stmt->fetch()) {
    $export_data[$row['time']][$row['pid']] = $row['value'];
}

$fixed_columns = array('time', 'v', 'eml', 'session', 'id');

if ($_GET['filetype'] == 'csv') {
    $columns_total = $stmt->columnCount();
    //output header - session data first
    foreach ($fixed_columns as $heading) {
        $output .= '"' . $heading . '",';
    }
    //then pid data headers
    foreach ($pids as $pid) {
        $output .= '"' . $pid['fullName'] . '",';
    }
    $output .= "\n";

    unset($fixed_columns[0]);

    // Get The Field Name
    foreach ($export_data as $key => $row) {
        $output .= '"' . $key . '",';
        foreach ($fixed_columns as $heading) {
            $output .= '"' . $session_data[$heading] . '",';
        }
        foreach ($pids as $pid) {
            if (isset($row[$pid['pid']])) {
                $output .= '"' . $row[$pid['pid']] . '",';
            } else {
                $output .= '"0",';
            }
        }
        $output .= '\n';
    }
    $output .= '\n';
    // Get Records from the table
    // Download the file
    $csvfilename = 'torque_session_' . $session_id . '.csv';
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=' . $csvfilename);

    echo $output;
    exit;
} elseif ($_GET['filetype'] == 'json') {
    $rows = array();
    while ($r = $stmt->fetch()) {
        $rows[] = $r;
    }
    $jsonrows = json_encode($rows);

    // Download the file
    $jsonfilename = 'torque_session_' . $session_id . '.json';
    header('Content-type: application/json');
    header('Content-Disposition: attachment; filename=' . $jsonfilename);

    echo $jsonrows;
    exit;
} else {
    exit;
}

?>
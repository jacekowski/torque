<?php
$torque = new TorqueViewer($pdo);

$torque->fetch_sessions();
$torque->fetch_session();

$session_id = $torque->session["sid"];
// Get data for session
$output = "";
$query = "SELECT * FROM raw_logs WHERE session=$session_id ORDER BY time ASC;";
$stmt = $pdo->query($query);

if ($_GET["filetype"] == "csv") {
    $columns_total = $stmt->columnCount();

    // Get The Field Name
    for ($i = 0; $i < $columns_total; $i++) {
        $col_meta = $stmt->getColumnMeta($i);
        $heading = $col_meta["name"];
        if (!in_array($heading, array("v", "eml", "time", "idx", "session","id"))) {
            if(strlen($heading)==2){
                $heading = "k0".substr($heading, 1);
            }
            $column_name = "userFullName" . substr($heading, 1);
            $query = "SELECT `" . $column_name . "` FROM userunit WHERE session=" . $session_id . " ORDER BY time DESC;";
            $stmt2 = $pdo->query($query);
            if ($stmt2 != false) {
                $row = $stmt2->fetch();
                $heading = $row[$column_name];
            }
        }
        $output .= '"' . $heading . '",';
    }
    $output .= "\n";

    // Get Records from the table
    while ($row = $stmt->fetch()) {
        for ($i = 0; $i < $columns_total; $i++) {
            $output .= '"' . $row["$i"] . '",';
        }
        $output .= "\n";
    }
    // Download the file
    $csvfilename = "torque_session_" . $session_id . ".csv";
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=' . $csvfilename);

    echo $output;
    exit;
} elseif ($_GET["filetype"] == "json") {
    $rows = array();
    while ($r = $stmt->fetch()) {
        $rows[] = $r;
    }
    $jsonrows = json_encode($rows);


    // Download the file
    $jsonfilename = "torque_session_" . $session_id . ".json";
    header('Content-type: application/json');
    header('Content-Disposition: attachment; filename=' . $jsonfilename);

    echo $jsonrows;
    exit;
} else {
    exit;
}


?>
<?php
function generate_front_page()
{
    $data['valid_permalink'] = false;
    if (isset($_GET['permalink'])) {
        $permalink_data = retrieve_permalink($_GET['permalink']);
        if ($permalink_data['valid_permalink'] == true) {
            generate_plot($permalink_data['session'], $permalink_data['variables'], $permalink_data['valid_permalink']);
        }
    } else {
        generate_plot(isset($_GET['id']) ? $_GET['id'] : null, isset($_GET['plotdata']) ? $_GET['plotdata'] : null, false);
    }
}

function session_list()
{
    global $pdo;
    global $twig;
    global $data;
    global $config;
    global $profile;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_sessions();
    $data['sessions'] = $torque->sessions;
    $data['sql_log'] = $pdo->returnLog();

    global $user;

    if ($user->is_authenticated()) {
        $buffer = $twig->render('session_list.twig', $data);
        if ($config['twig_profiling'] == true) {
            $dumper = new Twig\Profiler\Dumper\TextDumper();
            echo str_replace('%twig_profiling_placeholder%', $dumper->dump($profile), $buffer);
        } else {
            echo $buffer;
        }
    }

}

function generate_plot($session_id, $session_variables, $valid_permalink)
{
    global $pdo;
    global $twig;
    global $data;
    global $config;
    global $profile;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_sessions();
    $torque->fetch_session($session_id);
    $torque->select_pids($session_variables);
    $torque->fetch_session_data();
    $torque->create_geopath();

    $data['valid_permalink'] = $valid_permalink;
    $data['sessions'] = $torque->sessions;
    $data['session_data'] = $torque->session_data;
    $data['permalink'] = generate_permalink($torque->session_data['metadata']);
    $data['sql_log'] = $pdo->returnLog();

    global $user;

    if ($user->is_authenticated() or $data['valid_permalink'] == true) {
        $buffer = $twig->render('main.twig', $data);
        if ($config['twig_profiling'] == true) {
            $dumper = new TextDumper();
            echo str_replace('%twig_profiling_placeholder%', $dumper->dump($profile), $buffer);
        } else {
            echo $buffer;
        }
    }
}

function session_manager()
{
    global $pdo;
    global $twig;
    global $data;
    global $config;
    global $profile;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_sessions();
    $torque->fetch_session(isset($_GET['id']) ? $_GET['id'] : null);
    $torque->select_pids(null);
    $torque->fetch_session_data();

    $data['sessions'] = $torque->sessions;

    $data['first_session_data'] = $torque->session_data;

    //find previous session
    $keys = array_keys($torque->sessions);
    $arr_search = array_search($torque->session_data['metadata']['sid'], $keys) - 1;
    if ($arr_search < 0) {
        $arr_search = 0;
    }
    $next_session = $keys[$arr_search];
    $torque->fetch_session($next_session);
    $torque->select_pids(null);
    $torque->fetch_session_data();

    $data['second_session_data'] = $torque->session_data;

    $data['geolocs1'] = $data['first_session_data']['geolocs'];
    $data['geolocs2'] = $data['second_session_data']['geolocs'];

    $new_session_data['sid'] = $data['first_session_data']['metadata']['sid'];
    $new_session_data['MaxTime'] = $data['first_session_data']['metadata']['MaxTime'];
    $new_session_data['MinTime'] = $data['first_session_data']['metadata']['MinTime'];
    $new_session_data['SessionSize'] = $data['first_session_data']['metadata']['SessionSize'] + $data['second_session_data']['metadata']['SessionSize'];
    $new_session_data['SessionDistance'] = $data['first_session_data']['metadata']['SessionDistance'] + $data['second_session_data']['metadata']['SessionDistance'];
    $new_session_data['SessionFuel'] = $data['first_session_data']['metadata']['SessionFuel'] + $data['second_session_data']['metadata']['SessionFuel'];

    $data['session_gap'] = ($data['second_session_data']['metadata']['MinTime'] - $data['first_session_data']['metadata']['MaxTime']) / 1000;

    global $user;

    if ($_GET['manager'] == 'merge' and $user->is_authenticated() and $data['first_session_data']['metadata']['sid'] != $data['second_session_data']['metadata']['sid']) {
        //set sid of second session to first session's sid
        //start SQL transaction - need it to rollback in case something goes wrong (needs backend supporting transactions, like InnoDB)
        $pdo->beginTransaction();
        $query = 'UPDATE raw_logs_v2 SET session=:new_sid WHERE session=:old_sid;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':new_sid' => $new_session_data['sid'], ':old_sid' => $data['second_session_data']['sid']));
        $query = 'UPDATE profile_v2 SET MinTime=:min_time, MaxTime=:max_time, SessionSize=:session_size, SessionDistance=:session_distance, SessionFuel=:session_fuel WHERE session=:new_sid;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':new_sid' => $new_session_data['sid'],
            ':min_time' => $new_session_data['MinTime'],
            ':max_time' => $new_session_data['MaxTime'],
            ':session_size' => $new_session_data['SessionSize'],
            ':session_distance' => $new_session_data['SessionDistance'],
            ':session_fuel' => $new_session_data['SessionFuel']
        ));

        $query = 'DELETE FROM profile_v2 WHERE session=:old_sid;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':old_sid' => $data['second_session_data']['sid']));
        $query = 'DELETE FROM userunit_v2 WHERE session=:old_sid;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':old_sid' => $data['second_session_data']['sid']));
        $query = 'DELETE FROM defaultunit_v2 WHERE session=:old_sid;';
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':old_sid' => $data['second_session_data']['sid']));
        $pdo->commit();
    }

    $data['sql_log'] = $pdo->returnLog();

    global $user;

    if ($user->is_authenticated()) {
        $buffer = $twig->render('manager/manager_session.twig', $data);
        if ($config['twig_profiling'] == true) {
            $dumper = new TextDumper();
            echo str_replace('%twig_profiling_placeholder%', $dumper->dump($profile), $buffer);
        } else {
            echo $buffer;
        }
    }
}


function ajax_api()
{
    switch ($_GET['ajax']) {
        case 'maps':
            geolocs_data(isset($_GET['id']) ? $_GET['id'] : null, isset($_GET['plotdata']) ? $_GET['plotdata'] : null, false);
            break;
        case 'sessions':
            sessions_data(isset($_GET['id']) ? $_GET['id'] : null, isset($_GET['plotdata']) ? $_GET['plotdata'] : null, false);
            break;
        case 'all':
            ajax_all(isset($_GET['id']) ? $_GET['id'] : null, isset($_GET['plotdata']) ? $_GET['plotdata'] : null, false);
            break;
        case 'plot':
            ajax_plot(isset($_GET['id']) ? $_GET['id'] : null, isset($_GET['plotdata']) ? $_GET['plotdata'] : null, false);
            break;
        case 'main':
            global $twig;
            global $data;
            $buffer = $twig->render('main_ajax.twig', $data);
            echo $buffer;
            break;
        default:
            throw new Exception('Invalid request');
            break;
    }
}

function geolocs_data($session_id, $session_variables, $valid_permalink)
{
    global $pdo;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_sessions();
    $torque->fetch_session($session_id);
    $torque->select_pids($session_variables);
    $torque->fetch_session_data();

    echo json_encode(array('coordinates' => $torque->session_data['geopath']));
}

function sessions_data($session_id, $session_variables, $valid_permalink)
{
    global $pdo;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_sessions();

    echo json_encode(array('sessions' => $torque->sessions));
}

function ajax_all($session_id, $session_variables, $valid_permalink)
{
    global $pdo;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_session($session_id);
    $torque->select_pids($session_variables);
    $torque->fetch_session_data();
    $torque->create_geopath();

    $data['coordinates'] = $torque->session_data['geopath'];
    $data['bounds'] = $torque->session_data['bounds'];
    $data['pid_names'] = $torque->session_data['pid_names'];
    $data['actual_variables'] = $torque->session_data['actual_variables'];
    $data['plot_data'] = $torque->session_data['plot_data'];
    $data['metadata'] = $torque->session_data['metadata'];
    $data['permalink'] = generate_permalink($torque->session_data['metadata']);

    foreach ($data['plot_data'] as $key => $val) {
        if (!isset($val['display'])) {
            unset($data['plot_data'][$key]);
        } else {
            if ($val['display'] == false) {
                unset($data['plot_data'][$key]);
            }
        }
    }
    echo json_encode($data);
}

function ajax_plot($session_id, $session_variables, $valid_permalink)
{
    global $pdo;

    $torque = new TorqueViewer($pdo);
    $torque->fetch_session($session_id);
    $torque->select_pids($session_variables);
    $torque->fetch_session_data();

    $data['permalink'] = generate_permalink($torque->session_data['metadata']);
    $data['pid_names'] = $torque->session_data['pid_names'];
    $data['plot_data'] = $torque->session_data['plot_data'];

    foreach ($data['plot_data'] as $key => $val) {
        if (!isset($val['display'])) {
            unset($data['plot_data'][$key]);
        } else {
            if ($val['display'] == false) {
                unset($data['plot_data'][$key]);
            }
        }
    }
    echo json_encode($data);
}
?>
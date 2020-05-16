<?php
require_once('ldap_auth.php');

//Get variable from Browser-Request
function get_from_request($var)
{
    if (isset($_POST[$var])) {
        $val = $_POST[$var];
    } elseif (isset($_GET[$var])) {
        $val = $_GET[$var];
    } else {
        $val = '';
    }

    return $val;
}

function auth_user()
{
    global $user;
    $username = get_from_request('user');
    $password = get_from_request('pass');

    $user->authenticate($username,$password);
    return $user->is_authenticated();

}


class user_management
{
    protected $pdo;
    private $user_details = array();
    private $user_authenticated = false;
    private $username;

    function is_authenticated(){
        return $this->user_authenticated;
    }

    function __construct($pdo)
    {
        $this->pdo = $pdo;
        //attempt to load authentication data from session,
        if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] == true){
            $this->user_authenticated = $this->fetch_user_details($_SESSION['username']);
            $this->username = $_SESSION['username'];
        }
    }

    function __destruct()
    {
        $_SESSION['user_authenticated'] = $this->user_authenticated;
        if ($this->user_authenticated == true){
            $_SESSION['username'] = $this->username;
        }
        //TODO add saving to database
    }

    function authenticate($username, $password){
        $password_valid = false;

        $this->fetch_user_details($username);

        if ($this->get_user_parameter('authentication_type')){
            switch ($this->get_user_parameter('authentication_type')){
                case 'ldap':
                    $password_valid = validate_ldap_user($username,$password);
                    break;
                case 'mysql':
                    $password_valid = password_verify($password, $this->get_user_parameter('password'));
                    break;
                default:
                    $password_valid = false;
                    break;
            }
        }
        $this->user_authenticated = $password_valid;
        if ($password_valid){
            $this->username = $username;
        }
    }

    function fetch_user_details($username)
    {
        global $pdo;
        $details = array();
        $query = 'SELECT uid, name FROM users WHERE name = :name';
        $stmt = $pdo->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array(':name' => $username));
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $query = 'SELECT parameter, value FROM user_settings WHERE uid = :uid';
            $stmt = $pdo->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute(array(':uid' => $row['uid']));
            while ($row = $stmt->fetch()) {
                $details[$row['parameter']]['value'] = $row['value'];
                $details[$row['parameter']]['changed'] = false;
            }
            $this->user_details = $details;
            return true;
        } else {
            return false;
        }
    }

    function get_user_parameter($parameter_name){
        if (isset($this->user_details[$parameter_name]['value'])){
            return $this->user_details[$parameter_name]['value'];
        } else {
            return false;
        }
    }

    function set_user_parameter($parameter_name, $parameter_value){
        $details[$parameter_name]['value'] = $parameter_value;
        $details[$parameter_name]['changed'] = true;
    }
}

?>
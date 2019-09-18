<?php
if ($auth_mode == "ldap") {
    require_once('ldap_auth.php');
}
if ($auth_mode == "mysql") {
    require_once('mysql_auth.php');
}
if ($auth_mode == "ldap") {
    require_once('null_auth.php');
}

//Get variable from Browser-Request
function get_from_request($var)
{
    if (isset($_POST[$var])) {
        $val = $_POST[$var];
    } elseif (isset($_GET[$var])) {
        $val = $_GET[$var];
    } else {
        $val = "";
    }

    return $val;
}



//True if User/Pass match those of creds.php
//If both $auth_user and $auth_pass are empty, all passwords are accepted.
function auth_user()
{
    //global $auth_user, $auth_pass;

    $user = get_from_request("user");
    $pass = get_from_request("pass");

    //No User/Pass defined: Allow everything
    //if (empty($auth_user) && empty($auth_pass)) {
    //    return true;
    //}

    return validate_user($user, $pass);
    //if (($user == $auth_user) && ($pass == $auth_pass)) {
    //    return true;
    //}

    //return false;
}

?>
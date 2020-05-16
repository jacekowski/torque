<?php
function validate_ldap_user($username, $password)
{
    global $config;
    if (strlen($username) > 3 and strlen($password) >= 6) {
        // LDAP variables
        $ldap['user'] = $username;
        $ldap['pass'] = $password;
        $ldap['dn'] = 'uid=' . ldap_escape($ldap['user']) . ','.$config['ldap']['base'];

        // connecting to ldap
        $ldap['conn'] = ldap_connect($config['ldap']['host'], $config['ldap']['port']);

        ldap_set_option($ldap['conn'], LDAP_OPT_PROTOCOL_VERSION, 3);

        // binding to ldap
        $ldap['bind'] = @ldap_bind($ldap['conn'], $ldap['dn'], $ldap['pass']);
        if ($ldap['bind']) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

?>
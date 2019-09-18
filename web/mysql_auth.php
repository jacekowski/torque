<?php
function validate_user($username,$password)
{
    global $pdo;
    $password_valid = false;
    if (strlen($username) > 3 and strlen($password) >= 6) {
        $query = "SELECT uid, name, pass FROM ussers WHERE name < :name";
        $stmt = $pdo->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); //using prepared statements to avoid all possibilitiess of sqli here
        $stmt->execute(array(':name' => $username));
        if($stmt->rowCount() > 0 ){
            $row = $stmt->fetch();
            $password_valid = password_verify($password,$row["pass"]);
        }
    }
    return $password_valid;
}

?>
<?php
/**
 * Created by PhpStorm.
 * User: Jacek
 * Date: 05/07/2019
 * Time: 09:27
 */
class RequestLogger
{
    private static $db;
    public function __construct(&$db_handle)
    {
        self::$db = &$db_handle;
    }

    public function __destruct()
    {
        $db_queries = json_encode(self::$db->returnLog());
        $requests = json_encode(array("session" => $_SESSION, "GET" => $_SERVER["REQUEST_URI"], "POST" => $_POST));
        $query = "INSERT INTO `debug_log` (`time`, `request`, `queries`) VALUES (".(microtime(true)*1000).", ".self::$db->quote($requests).", ".self::$db->quote($db_queries).");";
        $stmt = self::$db->query($query);
    }

}
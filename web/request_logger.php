<?php
class RequestLogger
{
    private static $db;

    public function __construct(&$db_handle)
    {
        self::$db = &$db_handle;
    }

    public function __destruct()
    {
        global $config;
        $db_queries = json_encode(self::$db->returnLog());
        $requests = json_encode(array('session' => $_SESSION, 'GET' => $_SERVER['REQUEST_URI'], 'POST' => $_POST));

        $query = 'INSERT INTO `debug_log` (`time`, `request`, `queries`,`version`) VALUES (:time, :request , :queries, :version);';
        $stmt = self::$db->prepare($query);
        $stmt->execute(array(
            ':time' => (microtime(true) * 1000),
            ':request' => $requests,
            ':queries' => $db_queries,
            ':version' => $config['version']
        ));
    }
}

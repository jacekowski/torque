<?php

/**
 * Extends PDO and logs all queries that are executed and how long
 * they take, including queries issued via prepared statements
 */
class LoggedPDO extends PDO
{
    public static $log = array();

    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Print out the log when we're destructed. I'm assuming this will
     * be at the end of the page. If not you might want to remove this
     * destructor and manually call LoggedPDO::printLog();
     */
    public function __destruct()
    {
        //self::printLog();
    }


    public function query($query)
    {
        $start = microtime(true);
        $result = parent::query($query);
        $time = microtime(true) - $start;
        $err = parent::errorInfo();
        if ($err[0] != 0) {
            $query_result = $err[0];
        } else {
            $query_result = $result->rowCount();
        }
        LoggedPDO::$log[] = array('query' => $query,
            'time' => round($time * 1000, 3),
            'result' => $query_result,
            'source' => GetCallingMethodName(),
            'type' => 'query',
        );
        return $result;
    }

    /**
     * @return LoggedPDOStatement
     */
    public function prepare($query, $driver_options = null)
    {
        $start = microtime(true);
        if ($driver_options == null) {
            $stmt = new LoggedPDOStatement(parent::prepare($query));
        } else {
            $stmt = new LoggedPDOStatement(parent::prepare($query, $driver_options));
        }
        $time = microtime(true) - $start;
        $err = parent::errorInfo();
        if ($err[0] != 0) {
            $query_result = $err[0];
        } else {
            $query_result = 0;
        }
        LoggedPDO::$log[] = array('query' => '[PS] ' . $query,
            'time' => round($time * 1000, 3),
            'result' => $query_result,
            'source' => GetCallingMethodName(),
            'type' => 'prepared_statement',
        );

        return $stmt;
    }

    public static function returnLog()
    {
        return self::$log;
    }

    public function clearLog()
    {
        self::$log = array();
    }

    public static function printLog()
    {
        $totalTime = 0;
        $sql_log = '';
        $sql_log .= '<table border=1><tr><th>Query</th><th>Time (ms)</th><th>Error/Row Count</th></tr>';
        foreach (self::$log as $entry) {
            $totalTime += $entry['time'];
            //if ($entry['type'] == 'prepared_query') {
            //    $sql_log .= '<tr><td>' . $entry['query'] . var_export($entry['input_parameters'],true) . '</td><td>' . $entry['time'] . '</td><td>' . $entry['result'] . '</td></tr>'.'\n';
            //} else {
            $sql_log .= '<tr><td>' . $entry['query'] . '</td><td>' . $entry['time'] . '</td><td>' . $entry['result'] . '</td></tr>' . '\n';
            //}
        }
        $sql_log .= '<tr><th>' . count(self::$log) . ' queries</th><th>' . $totalTime . '</th></tr>' . '\n';
        $sql_log .= '</table>';
        return $sql_log;
    }
}

/**
 * PDOStatement decorator that logs when a PDOStatement is
 * executed, and the time it took to run
 * @see LoggedPDO
 */
class LoggedPDOStatement
{
    /**
     * The PDOStatement we decorate
     */
    private $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * When execute is called record the time it takes and
     * then log the query
     * @param null $input_parameters
     * @return bool
     */
    public function execute($input_parameters = null)
    {
        $start = microtime(true);
        if ($input_parameters == null) {
            $result = $this->statement->execute();
        } else {
            $result = $this->statement->execute($input_parameters);
        }

        $err = $this->statement->errorInfo();
        if ($err[0] != 0) {
            $query_result = $err[0];
        } else {
            $query_result = $this->statement->rowCount();
        }


        $time = microtime(true) - $start;
        LoggedPDO::$log[] = array('query' => '[PQ] ' . $this->statement->queryString,
            'time' => round($time * 1000, 3),
            'result' => $query_result,
            'source' => GetCallingMethodName(),
            'input_parameters' => $input_parameters,
            'type' => 'prepared_query',
        );
        return $result;
    }

    /**
     * Other than execute pass all other calls to the PDOStatement object
     * @param string $function_name
     * @param array $parameters arguments
     * @return mixed results
     */
    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}

?>
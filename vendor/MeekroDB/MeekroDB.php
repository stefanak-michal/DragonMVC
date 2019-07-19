<?php

namespace MeekroDB;

use mysqli, mysqli_result;

/**
 * MeekroDB
 * 
 * @see http://www.meekro.com/docs.php
 */
final class MeekroDB
{
    // initial connection
    public $dbName = '';
    public $user = '';
    public $password = '';
    public $host = 'localhost';
    public $port = null;
    public $encoding = 'utf8';
    // configure workings
    public $param_char = '%';
    public $named_param_seperator = '_';
    public $success_handler = false;
    public $error_handler = true;
    public $throw_exception_on_error = false;
    public $nonsql_error_handler = null;
    public $throw_exception_on_nonsql_error = false;
    public $nested_transactions = false;
    public $usenull = true;
    public $ssl = array('key' => '', 'cert' => '', 'ca_cert' => '', 'ca_path' => '', 'cipher' => '');
    public $connect_options = array(MYSQLI_OPT_CONNECT_TIMEOUT => 30);
    // internal
    public $internal_mysql = null;
    public $server_info = null;
    public $insert_id = 0;
    public $num_rows = 0;
    public $affected_rows = 0;
    public $current_db = null;
    public $nested_transactions_count = 0;

    public function __construct($host = null, $user = null, $password = null, $dbName = null, $port = null, $encoding = null)
    {
        if (!is_null($host))
            $this->host = $host;
        if (!is_null($user))
            $this->user = $user;
        if (!is_null($password))
            $this->password = $password;
        if (!is_null($dbName))
            $this->dbName = $dbName;
        if (!is_null($port))
            $this->port = $port;
        if (!is_null($encoding))
            $this->encoding = $encoding;
    }

    public function get()
    {
        $mysql = $this->internal_mysql;

        if (!($mysql instanceof mysqli)) {
            if (!$this->port)
                $this->port = ini_get('mysqli.default_port');
            $this->current_db = $this->dbName;
            $mysql = new mysqli();

            $connect_flags = 0;
            if ($this->ssl['key']) {
                $mysql->ssl_set($this->ssl['key'], $this->ssl['cert'], $this->ssl['ca_cert'], $this->ssl['ca_path'], $this->ssl['cipher']);
                $connect_flags |= MYSQLI_CLIENT_SSL;
            }
            foreach ($this->connect_options as $key => $value) {
                $mysql->options($key, $value);
            }

            // suppress warnings, since we will check connect_error anyway
            @$mysql->real_connect($this->host, $this->user, $this->password, $this->dbName, $this->port, null, $connect_flags);

            if ($mysql->connect_error) {
                $this->nonSQLError('Unable to connect to MySQL server! Error: ' . $mysql->connect_error);
            }

            $mysql->set_charset($this->encoding);
            $this->internal_mysql = $mysql;
            $this->server_info = $mysql->server_info;
        }

        return $mysql;
    }

    /**
     * Drop any existing MySQL connections. If you run a query after this, it will automatically reconnect.
     *
     * @see https://meekro.com/docs.php#anchor_disconnect
     */
    public function disconnect()
    {
        $mysqli = $this->internal_mysql;
        if ($mysqli instanceof mysqli) {
            if ($thread_id = $mysqli->thread_id)
                $mysqli->kill($thread_id);
            $mysqli->close();
        }
        $this->internal_mysql = null;
    }

    /**
     * @param string $message
     * @throws MeekroDBException
     * @return void
     */
    public function nonSQLError($message)
    {
        if ($this->throw_exception_on_nonsql_error) {
            $e = new MeekroDBException($message);
            throw $e;
        }

        $error_handler = is_callable($this->nonsql_error_handler) ? $this->nonsql_error_handler : array($this, 'meekrodb_error_handler');

        call_user_func($error_handler, array(
            'type' => 'nonsql',
            'error' => $message
        ));
    }

    /**
     * @see https://meekro.com/docs.php#anchor_debugmode
     * @param bool $handler
     */
    public function debugMode($handler = true)
    {
        $this->success_handler = $handler;
    }

    public function serverVersion()
    {
        $this->get();
        return $this->server_info;
    }

    public function transactionDepth()
    {
        return $this->nested_transactions_count;
    }

    /**
     * Returns the auto incrementing ID for the last insert statement. The insert could have been done through DB::insert() or DB::query().
     *
     * @see https://meekro.com/docs.php#anchor_insertid
     * @return int
     */
    public function insertId()
    {
        return $this->insert_id;
    }

    /**
     * Returns the number of rows changed by the last update statement. That statement could have been run through DB::update() or DB::query().
     *
     * @see https://meekro.com/docs.php#anchor_affectedrows
     * @return int
     */
    public function affectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * Counts the number of rows returned by the last query. Ignores queries done with DB::queryFirstRow() and DB::queryFirstField().
     *
     * @see https://meekro.com/docs.php#anchor_count
     * @return mixed
     */
    public function count()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'numRows'), $args);
    }

    public function numRows()
    {
        return $this->num_rows;
    }

    /**
     * Switch to a different database.
     *
     * @see https://meekro.com/docs.php#anchor_usedb
     * @return mixed
     */
    public function useDB()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'setDB'), $args);
    }

    public function setDB($dbName)
    {
        $db = $this->get();
        if (!$db->select_db($dbName))
            $this->nonSQLError("Unable to set database to $dbName");
        $this->current_db = $dbName;
    }

    /**
     * These are merely shortcuts for the three standard transaction commands: START TRANSACTION, COMMIT, and ROLLBACK.
     * When DB::$nested_transactions are enabled, these commands can be used to have multiple layered transactions. Otherwise, running DB::startTransaction() when a transaction is active will auto-commit that transaction and start a new one.
     *
     * @see https://meekro.com/docs.php#anchor_transaction
     * @return int|void
     * @throws MeekroDBException
     */
    public function startTransaction()
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            return $this->nonSQLError("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
        }

        if (!$this->nested_transactions || $this->nested_transactions_count == 0) {
            $this->query('START TRANSACTION');
            $this->nested_transactions_count = 1;
        } else {
            $this->query("SAVEPOINT LEVEL{$this->nested_transactions_count}");
            $this->nested_transactions_count++;
        }

        return $this->nested_transactions_count;
    }

    /**
     * @see https://meekro.com/docs.php#anchor_transaction
     * @param bool $all
     * @return int|void
     * @throws MeekroDBException
     */
    public function commit($all = false)
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            return $this->nonSQLError("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
        }

        if ($this->nested_transactions && $this->nested_transactions_count > 0)
            $this->nested_transactions_count--;

        if (!$this->nested_transactions || $all || $this->nested_transactions_count == 0) {
            $this->nested_transactions_count = 0;
            $this->query('COMMIT');
        } else {
            $this->query("RELEASE SAVEPOINT LEVEL{$this->nested_transactions_count}");
        }

        return $this->nested_transactions_count;
    }

    /**
     * @see https://meekro.com/docs.php#anchor_transaction
     * @param bool $all
     * @return int|void
     * @throws MeekroDBException
     */
    public function rollback($all = false)
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            return $this->nonSQLError("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
        }

        if ($this->nested_transactions && $this->nested_transactions_count > 0)
            $this->nested_transactions_count--;

        if (!$this->nested_transactions || $all || $this->nested_transactions_count == 0) {
            $this->nested_transactions_count = 0;
            $this->query('ROLLBACK');
        } else {
            $this->query("ROLLBACK TO SAVEPOINT LEVEL{$this->nested_transactions_count}");
        }

        return $this->nested_transactions_count;
    }

    protected function formatTableName($table)
    {
        $table = trim($table, '`');

        if (strpos($table, '.'))
            return implode('.', array_map(array($this, 'formatTableName'), explode('.', $table)));
        else
            return '`' . str_replace('`', '``', $table) . '`';
    }

    /**
     * Run an UPDATE command by specifying an array of changes to make, and a WHERE component. The WHERE component can have parameters in the same style as the query() command. As with insert() and replace(), you can use DB::sqleval() to pass a function directly to MySQL for evaluation.
     *
     * @see https://meekro.com/docs.php#anchor_update
     * @return mixed
     */
    public function update()
    {
        $args = func_get_args();
        $table = array_shift($args);
        $params = array_shift($args);
        $where = array_shift($args);

        $query = str_replace('%', $this->param_char, "UPDATE %b SET %? WHERE ") . $where;

        array_unshift($args, $params);
        array_unshift($args, $table);
        array_unshift($args, $query);
        return call_user_func_array(array($this, 'query'), $args);
    }

    public function insertOrReplace($which, $table, $datas, $options = array())
    {
        $datas = unserialize(serialize($datas)); // break references within array
        $keys = $values = array();

        if (isset($datas[0]) && is_array($datas[0])) {
            foreach ($datas as $datum) {
                ksort($datum);
                if (!$keys)
                    $keys = array_keys($datum);
                $values[] = array_values($datum);
            }
        } else {
            $keys = array_keys($datas);
            $values = array_values($datas);
        }

        if (isset($options['ignore']) && $options['ignore'])
            $which = 'INSERT IGNORE';

        if (isset($options['update']) && is_array($options['update']) && $options['update'] && strtolower($which) == 'insert') {
            if (array_values($options['update']) !== $options['update']) {
                return $this->query(
                    str_replace('%', $this->param_char, "INSERT INTO %b %lb VALUES %? ON DUPLICATE KEY UPDATE %?"),
                    $table,
                    $keys,
                    $values,
                    $options['update']
                );
            } else {
                $update_str = array_shift($options['update']);
                $query_param = array(
                    str_replace('%', $this->param_char, "INSERT INTO %b %lb VALUES %? ON DUPLICATE KEY UPDATE ") . $update_str,
                    $table, $keys, $values
                );
                $query_param = array_merge($query_param, $options['update']);
                return call_user_func_array(array($this, 'query'), $query_param);
            }
        }

        return $this->query(
            str_replace('%', $this->param_char, "%l INTO %b %lb VALUES %?"),
            $which,
            $table,
            $keys,
            $values
        );
    }

    /**
     * Either INSERT or REPLACE a row into a table. You can use DB::sqleval() to force something to be passed directly to MySQL and not escaped. DB::sqleval() does nothing on its own, outside of the insert/replace/update/delete commands.
     * You may insert multiple rows at once by passing an array of associative arrays.
     *
     * @see https://meekro.com/docs.php#anchor_insert
     * @param $table
     * @param $data
     * @return mixed
     */
    public function insert($table, $data)
    {
        return $this->insertOrReplace('INSERT', $table, $data);
    }

    /**
     * Works like INSERT, except it does an INSERT IGNORE statement. Won't give a MySQL error if the primary key is already taken.
     *
     * @see https://meekro.com/docs.php#anchor_insertignore
     * @param $table
     * @param $data
     * @return mixed
     */
    public function insertIgnore($table, $data)
    {
        return $this->insertOrReplace('INSERT', $table, $data, array('ignore' => true));
    }

    /**
     * @see insert
     * @param $table
     * @param $data
     * @return mixed
     */
    public function replace($table, $data)
    {
        return $this->insertOrReplace('REPLACE', $table, $data);
    }

    /**
     * Similar to INSERT, except it does an INSERT ... ON DUPLICATE KEY UPDATE. After the usual insert syntax, you can specify one of three things: a query-like string with the update component, a second associative array with the keys and values to update, or nothing, in which case the INSERT associative array gets re-used.
     *
     * @see https://meekro.com/docs.php#anchor_insertupdate
     * @return mixed
     * @throws MeekroDBException
     */
    public function insertUpdate()
    {
        $args = func_get_args();
        $table = array_shift($args);
        $data = array_shift($args);

        if (!isset($args[0])) { // update will have all the data of the insert
            if (isset($data[0]) && is_array($data[0])) { //multiple insert rows specified -- failing!
                $this->nonSQLError("Badly formatted insertUpdate() query -- you didn't specify the update component!");
            }

            $args[0] = $data;
        }

        if (is_array($args[0]))
            $update = $args[0];
        else
            $update = $args;

        return $this->insertOrReplace('INSERT', $table, $data, array('update' => $update));
    }

    /**
     * Run the MySQL DELETE command with the given WHERE conditions.
     *
     * @see https://meekro.com/docs.php#anchor_delete
     * @return mixed
     */
    public function delete()
    {
        $args = func_get_args();
        $table = $this->formatTableName(array_shift($args));
        $where = array_shift($args);
        $buildquery = "DELETE FROM $table WHERE $where";
        array_unshift($args, $buildquery);
        return call_user_func_array(array($this, 'query'), $args);
    }

    /**
     * @see insert
     * @return MeekroDBEval
     */
    public function sqleval()
    {
        $args = func_get_args();
        $text = call_user_func_array(array($this, 'parseQueryParams'), $args);
        return new MeekroDBEval($text);
    }

    /**
     * Get an array of the columns in the requested table.
     *
     * @see https://meekro.com/docs.php#anchor_columnlist
     * @param $table
     * @return array
     */
    public function columnList($table)
    {
        return $this->queryOneColumn('Field', "SHOW COLUMNS FROM %b", $table);
    }

    /**
     * Get an array of the tables in either the current database, or the requested one.
     *
     * @see https://meekro.com/docs.php#anchor_tablelist
     * @param null $db
     * @return array
     */
    public function tableList($db = null)
    {
        if ($db) {
            $olddb = $this->current_db;
            $this->useDB($db);
        }

        $result = $this->queryFirstColumn('SHOW TABLES');
        if (isset($olddb))
            $this->useDB($olddb);
        return $result;
    }

    protected function preparseQueryParams()
    {
        $args = func_get_args();
        $sql = trim(strval(array_shift($args)));
        $args_all = $args;

        if (count($args_all) == 0)
            return array($sql);

        $param_char_length = strlen($this->param_char);
        $named_seperator_length = strlen($this->named_param_seperator);

        $types = array(
            $this->param_char . 'll', // list of literals
            $this->param_char . 'ls', // list of strings
            $this->param_char . 'l', // literal
            $this->param_char . 'li', // list of integers
            $this->param_char . 'ld', // list of decimals
            $this->param_char . 'lb', // list of backticks
            $this->param_char . 'lt', // list of timestamps
            $this->param_char . 's', // string
            $this->param_char . 'i', // integer
            $this->param_char . 'd', // double / decimal
            $this->param_char . 'b', // backtick
            $this->param_char . 't', // timestamp
            $this->param_char . '?', // infer type
            $this->param_char . 'ss'  // search string (like string, surrounded with %'s)
        );

        // generate list of all MeekroDB variables in our query, and their position
        // in the form "offset => variable", sorted by offsets
        $posList = array();
        foreach ($types as $type) {
            $lastPos = 0;
            while (($pos = strpos($sql, $type, $lastPos)) !== false) {
                $lastPos = $pos + 1;
                if (isset($posList[$pos]) && strlen($posList[$pos]) > strlen($type))
                    continue;
                $posList[$pos] = $type;
            }
        }

        ksort($posList);

        // for each MeekroDB variable, substitute it with array(type: i, value: 53) or whatever
        $chunkyQuery = array(); // preparsed query
        $pos_adj = 0; // how much we've added or removed from the original sql string
        foreach ($posList as $pos => $type) {
            $type = substr($type, $param_char_length); // variable, without % in front of it
            $length_type = strlen($type) + $param_char_length; // length of variable w/o %

            $new_pos = $pos + $pos_adj; // position of start of variable
            $new_pos_back = $new_pos + $length_type; // position of end of variable
            $arg_number_length = 0; // length of any named or numbered parameter addition
            // handle numbered parameters
            if ($arg_number_length = strspn($sql, '0123456789', $new_pos_back)) {
                $arg_number = substr($sql, $new_pos_back, $arg_number_length);
                if (!array_key_exists($arg_number, $args_all)) {
                    $this->nonSQLError("Non existent argument reference (arg $arg_number): $sql");
                }

                $arg = $args_all[$arg_number];

                // handle named parameters
            } else if (substr($sql, $new_pos_back, $named_seperator_length) == $this->named_param_seperator) {
                $arg_number_length = strspn($sql, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_', $new_pos_back + $named_seperator_length) + $named_seperator_length;

                $arg_number = substr($sql, $new_pos_back + $named_seperator_length, $arg_number_length - $named_seperator_length);
                $named_args = array_slice($args_all, -1)[0];

                if (count($named_args) == 0 || !is_array($named_args)) {
                    $this->nonSQLError("If you use named parameters, the second argument must be an array of parameters");
                }
                if (!array_key_exists($arg_number, $named_args)) {
                    $this->nonSQLError("Non existent argument reference (arg $arg_number): $sql");
                }

                $arg = $named_args[$arg_number];
            } else {
                $arg_number = 0;
                $arg = array_shift($args);
            }

            if ($new_pos > 0) {
                $chunkyQuery[] = substr($sql, 0, $new_pos);
            }

            if (is_object($arg) && ($arg instanceof WhereClause)) {
                list($clause_sql, $clause_args) = $arg->textAndArgs();
                array_unshift($clause_args, $clause_sql);
                $preparsed_sql = call_user_func_array(array($this, 'preparseQueryParams'), $clause_args);
                $chunkyQuery = array_merge($chunkyQuery, $preparsed_sql);
            } else {
                $chunkyQuery[] = array('type' => $type, 'value' => $arg);
            }

            $sql = substr($sql, $new_pos_back + $arg_number_length);
            $pos_adj -= $new_pos_back + $arg_number_length;
        }

        if (strlen($sql) > 0)
            $chunkyQuery[] = $sql;

        return $chunkyQuery;
    }

    protected function escape($str)
    {
        return "'" . $this->get()->real_escape_string(strval($str)) . "'";
    }

    protected function sanitize($value)
    {
        if (is_object($value)) {
            if ($value instanceof MeekroDBEval)
                return $value->text;
            else if ($value instanceof DateTime)
                return $this->escape($value->format('Y-m-d H:i:s'));
            else
                return '';
        }

        if (is_null($value))
            return $this->usenull ? 'NULL' : "''";
        else if (is_bool($value))
            return ($value ? 1 : 0);
        else if (is_int($value))
            return $value;
        else if (is_float($value))
            return $value;

        else if (is_array($value)) {
            // non-assoc array?
            if (array_values($value) === $value) {
                if (is_array($value[0]))
                    return implode(', ', array_map(array($this, 'sanitize'), $value));
                else
                    return '(' . implode(', ', array_map(array($this, 'sanitize'), $value)) . ')';
            }

            $pairs = array();
            foreach ($value as $k => $v) {
                $pairs[] = $this->formatTableName($k) . '=' . $this->sanitize($v);
            }

            return implode(', ', $pairs);
        } else
            return $this->escape($value);
    }

    protected function parseTS($ts)
    {
        if (is_string($ts))
            return date('Y-m-d H:i:s', strtotime($ts));
        else if (is_object($ts) && ($ts instanceof DateTime))
            return $ts->format('Y-m-d H:i:s');
    }

    protected function intval($var)
    {
        if (PHP_INT_SIZE == 8)
            return intval($var);
        return floor(doubleval($var));
    }

    protected function parseQueryParams()
    {
        $args = func_get_args();
        $chunkyQuery = call_user_func_array(array($this, 'preparseQueryParams'), $args);

        $query = '';
        $array_types = array('ls', 'li', 'ld', 'lb', 'll', 'lt');

        foreach ($chunkyQuery as $chunk) {
            if (is_string($chunk)) {
                $query .= $chunk;
                continue;
            }

            $type = $chunk['type'];
            $arg = $chunk['value'];
            $result = '';

            if ($type != '?') {
                $is_array_type = in_array($type, $array_types, true);
                if ($is_array_type && !is_array($arg))
                    $this->nonSQLError("Badly formatted SQL query: Expected array, got scalar instead!");
                else if (!$is_array_type && is_array($arg))
                    $this->nonSQLError("Badly formatted SQL query: Expected scalar, got array instead!");
            }

            if ($type == 's')
                $result = $this->escape($arg);
            else if ($type == 'i')
                $result = $this->intval($arg);
            else if ($type == 'd')
                $result = doubleval($arg);
            else if ($type == 'b')
                $result = $this->formatTableName($arg);
            else if ($type == 'l')
                $result = $arg;
            else if ($type == 'ss')
                $result = $this->escape("%" . str_replace(array('%', '_'), array('\%', '\_'), $arg) . "%");
            else if ($type == 't')
                $result = $this->escape($this->parseTS($arg));

            else if ($type == 'ls')
                $result = array_map(array($this, 'escape'), $arg);
            else if ($type == 'li')
                $result = array_map(array($this, 'intval'), $arg);
            else if ($type == 'ld')
                $result = array_map('doubleval', $arg);
            else if ($type == 'lb')
                $result = array_map(array($this, 'formatTableName'), $arg);
            else if ($type == 'll')
                $result = $arg;
            else if ($type == 'lt')
                $result = array_map(array($this, 'escape'), array_map(array($this, 'parseTS'), $arg));

            else if ($type == '?')
                $result = $this->sanitize($arg);
            else
                $this->nonSQLError("Badly formatted SQL query: Invalid MeekroDB param $type");

            if (is_array($result))
                $result = '(' . implode(',', $result) . ')';

            $query .= $result;
        }

        return $query;
    }

    protected function prependCall($function, $args, $prepend)
    {
        array_unshift($args, $prepend);
        return call_user_func_array($function, $args);
    }

    /**
     * The first parameter is a query string with placeholders variables. Following that, you must have an additional parameter for every placeholder variable.
     *
     * @see https://meekro.com/docs.php#anchor_query
     * @return mixed
     */
    public function query()
    {
        $args = func_get_args();
        return $this->prependCall(array($this, 'queryHelper'), $args, 'assoc');
    }

    public function queryAllLists()
    {
        $args = func_get_args();
        return $this->prependCall(array($this, 'queryHelper'), $args, 'list');
    }

    /**
     * Like DB::query(), except the keys for each associative array will be in the form TableName.ColumnName. Useful if you're joining several tables, and they each have an id field.
     *
     * @see https://meekro.com/docs.php#anchor_queryfullcolumns
     * @return mixed
     */
    public function queryFullColumns()
    {
        $args = func_get_args();
        return $this->prependCall(array($this, 'queryHelper'), $args, 'full');
    }

    /**
     * Like DB::query(), except it returns a standard MySQLi_Result object instead of an array of associative arrays. This is intended for situations where the result set is huge, and PHP's memory is not enough to store the whole thing all at once.
     *
     * @see https://meekro.com/docs.php#anchor_queryraw
     * @return mixed
     */
    public function queryRaw()
    {
        $args = func_get_args();
        return $this->prependCall(array($this, 'queryHelper'), $args, 'raw_buf');
    }

    public function queryRawUnbuf()
    {
        $args = func_get_args();
        return $this->prependCall(array($this, 'queryHelper'), $args, 'raw_unbuf');
    }

    protected function queryHelper()
    {
        $args = func_get_args();
        $type = array_shift($args);
        $db = $this->get();

        $is_buffered = true;
        $row_type = 'assoc'; // assoc, list, raw
        $full_names = false;

        switch ($type) {
            case 'assoc':
                break;
            case 'list':
                $row_type = 'list';
                break;
            case 'full':
                $row_type = 'list';
                $full_names = true;
                break;
            case 'raw_buf':
                $row_type = 'raw';
                break;
            case 'raw_unbuf':
                $is_buffered = false;
                $row_type = 'raw';
                break;
            default:
                $this->nonSQLError('Error -- invalid argument to queryHelper!');
        }

        $sql = call_user_func_array(array($this, 'parseQueryParams'), $args);

        //get select explain
        $explain = [];
        $doExplain = false;
        if ($this->success_handler && stripos($sql, 'select') === 0) {
            $doExplain = true;

            //prevent explain in special queries
            preg_match("/select (\w+)\(\)/i", $sql, $command);
            if (!empty($command[1])) {
                $command = strtoupper($command[1]);

                if (in_array($command, ['BENCHMARK', 'CHARSET', 'COERCIBILITY', 'COLLATION', 'CONNECTION_ID', 'CURRENT_USER', 'DATABASE', 'FOUND_ROWS', 'LAST_INSERT_ID', 'ROW_COUNT', 'SCHEMA', 'SESSION_USER', 'SYSTEM_USER', 'USER', 'VERSION'])) {
                    $doExplain = false;
                }
            }
        }

        if ($doExplain) {
            $result = $db->query('explain ' . $sql);

            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $explain[] = $row;
                }
                $result->free();
            }

            unset($result);
        }

        if ($this->success_handler) {
            $starttime = microtime(true);
        }
        $result = $db->query($sql, $is_buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT);
        if ($this->success_handler) {
            $runtime = microtime(true) - $starttime;
        } else {
            $runtime = 0;
        }

        // ----- BEGIN ERROR HANDLING
        if (!$sql || $db->error) {
            if ($this->error_handler) {
                $error_handler = is_callable($this->error_handler) ? $this->error_handler : array($this, 'meekrodb_error_handler');

                call_user_func($error_handler, array(
                    'type' => 'sql',
                    'query' => $sql,
                    'error' => $db->error,
                    'code' => $db->errno
                ));
            }

            if ($this->throw_exception_on_error) {
                $e = new MeekroDBException($db->error, $sql, $db->errno);
                throw $e;
            }
        } else if ($this->success_handler) {
            $runtime = sprintf('%f', $runtime * 1000);
            $success_handler = is_callable($this->success_handler) ? $this->success_handler : array($this, 'meekrodb_debugmode_handler');

            call_user_func($success_handler, array(
                'query' => $sql,
                'explain' => $explain ?: null,
                'runtime' => $runtime,
                'affected' => $db->affected_rows
            ));
        }

        // ----- END ERROR HANDLING

        $this->insert_id = $db->insert_id;
        $this->affected_rows = $db->affected_rows;

        // mysqli_result->num_rows won't initially show correct results for unbuffered data
        if ($is_buffered && ($result instanceof mysqli_result)) {
            $this->num_rows = $result->num_rows;
        } else {
            $this->num_rows = null;
        }

        if ($row_type == 'raw' || !($result instanceof mysqli_result)) {
            return $result;
        }

        $return = array();

        if ($full_names) {
            $infos = array();
            foreach ($result->fetch_fields() as $info) {
                if (strlen($info->table)) {
                    $infos[] = $info->table . '.' . $info->name;
                } else {
                    $infos[] = $info->name;
                }
            }
        }

        while ($row = ($row_type == 'assoc' ? $result->fetch_assoc() : $result->fetch_row())) {
            if ($full_names) {
                $row = array_combine($infos, $row);
            }
            $return[] = $row;
        }

        // free results
        $result->free();
        while ($db->more_results()) {
            $db->next_result();
            if ($result = $db->use_result()) {
                $result->free();
            }
        }

        return $return;
    }

    public function queryOneRow()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'queryFirstRow'), $args);
    }

    /**
     * Retrieve the first row of results for the query, and return it as an associative array. If the query returned no rows, this returns null.
     *
     * @see https://meekro.com/docs.php#anchor_queryfirstrow
     * @return mixed|null
     */
    public function queryFirstRow()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this, 'query'), $args);
        if (!$result || !is_array($result))
            return null;
        return reset($result);
    }

    public function queryOneList()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'queryFirstList'), $args);
    }

    /**
     * Retrieve the first row of results for the query, and return it as a numbered index (non-associative) array. If the query returned no rows, this returns null.
     *
     * @see https://meekro.com/docs.php#anchor_queryfirstlist
     * @return mixed|null
     */
    public function queryFirstList()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this, 'queryAllLists'), $args);
        if (!$result || !is_array($result))
            return null;
        return reset($result);
    }

    /**
     * Retrieve the first column of results for the query, and return it as a regular array. If the query returned no rows, this returns an empty array.
     *
     * @see https://meekro.com/docs.php#anchor_queryfirstcolumn
     * @return array
     */
    public function queryFirstColumn()
    {
        $args = func_get_args();
        $results = call_user_func_array(array($this, 'queryAllLists'), $args);
        $ret = array();

        if (!count($results) || !count($results[0]))
            return $ret;

        foreach ($results as $row) {
            $ret[] = $row[0];
        }

        return $ret;
    }

    /**
     * Retrieve the requested column of results from the query, and return it as a regular array. If the query returned no rows, or the requested column isn't in the result set, this returns an empty array.
     *
     * @see https://meekro.com/docs.php#anchor_queryonecolumn
     * @return array
     */
    public function queryOneColumn()
    {
        $args = func_get_args();
        $column = array_shift($args);
        $results = call_user_func_array(array($this, 'query'), $args);
        $ret = array();

        if (!count($results) || !count($results[0]))
            return $ret;
        if ($column === null) {
            $keys = array_keys($results[0]);
            $column = $keys[0];
        }

        foreach ($results as $row) {
            $ret[] = $row[$column];
        }

        return $ret;
    }

    /**
     * Get the contents of the first field from the first row of results, and return that. If no rows were returned by the query, this returns null.
     *
     * @see https://meekro.com/docs.php#anchor_queryfirstfield
     * @return null
     */
    public function queryFirstField()
    {
        $args = func_get_args();
        $row = call_user_func_array(array($this, 'queryFirstList'), $args);
        if ($row == null)
            return null;
        return $row[0];
    }

    /**
     * Get the contents of the requested field from the first row of results, and return that. If no rows were returned by the query, this returns null.
     *
     * @see https://meekro.com/docs.php#anchor_queryonefield
     * @return null
     */
    public function queryOneField()
    {
        $args = func_get_args();
        $column = array_shift($args);

        $row = call_user_func_array(array($this, 'queryOneRow'), $args);
        if ($row == null) {
            return null;
        } else if ($column === null) {
            $keys = array_keys($row);
            $column = $keys[0];
        }

        return $row[$column];
    }

    private function meekrodb_error_handler($params)
    {
        if (isset($params['query']))
            $out[] = "QUERY: " . $params['query'];
        if (isset($params['error']))
            $out[] = "ERROR: " . $params['error'];
        $out[] = "";

        if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            echo implode("\n", $out);
        } else {
            echo implode("<br>\n", $out);
        }

        die;
    }

    private function meekrodb_debugmode_handler($params)
    {
        echo "QUERY: " . $params['query'] . " [" . $params['runtime'] . " ms]";
        if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            echo "\n";
        } else {
            echo "<br>\n";
        }
    }
}

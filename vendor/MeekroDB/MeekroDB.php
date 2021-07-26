<?php

namespace MeekroDB;

use mysqli, mysqli_result;

/**
 * MeekroDB
 * - I took his one file and put it into classes and namespaces.
 * - Also I've added annotations and types.
 * - Removed all call_user_func and call_user_func_args and replaced with direct invoke.
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @author Michal Stefanak
 * @package MeekroDB
 * @see http://www.meekro.com/docs
 * @see https://github.com/SergeyTsalkov/meekrodb
 */
final class MeekroDB
{
    // initial connection
    private $dbName = '';
    private $user = '';
    private $password = '';
    private $host = 'localhost';
    private $port = 3306;
    private $socket = null;
    private $encoding = 'utf8';

    // configure workings
    public $param_char = '%';
    public $named_param_seperator = '_';
    public $nested_transactions = false;
    public $ssl = array('key' => '', 'cert' => '', 'ca_cert' => '', 'ca_path' => '', 'cipher' => '');
    public $connect_options = array(MYSQLI_OPT_CONNECT_TIMEOUT => 30);
    public $logfile;

    // internal
    private $internal_mysql = null;
    private $server_info = null;
    private $insert_id = 0;
    private $num_rows = 0;
    private $affected_rows = 0;
    private $current_db = null;
    private $nested_transactions_count = 0;
    private $last_query;

    private $hooks = array(
        'pre_parse' => array(),
        'pre_run' => array(),
        'post_run' => array(),
        'run_success' => array(),
        'run_failed' => array(),
    );

    /**
     * MeekroDB constructor.
     * Before you run any SQL commands, you must set the variables below. MeekroDB doesn't actually establish a MySQL connection until you run your first command!
     *
     * @see https://meekro.com/docs/connection.html
     * @param string|null $host
     * @param string|null $user
     * @param string|null $password
     * @param string|null $dbName
     * @param int|null $port
     * @param string|null $encoding
     * @param resource|null $socket
     */
    public function __construct(?string $host = null, ?string $user = null, ?string $password = null, ?string $dbName = null, ?int $port = null, ?string $encoding = null, $socket = null)
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
        if (!is_null($socket))
            $this->socket = $socket;
        if (!is_null($encoding))
            $this->encoding = $encoding;
    }

    /**
     * Get connection resource
     *
     * @return mysqli|null
     * @throws MeekroDBException
     */
    private function get(): ?mysqli
    {
        $mysql = $this->internal_mysql;

        if (!($mysql instanceof MySQLi)) {
            if (!$this->port) $this->port = ini_get('mysqli.default_port');
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
            @$mysql->real_connect($this->host, $this->user, $this->password, $this->dbName, $this->port, $this->socket, $connect_flags);

            if ($mysql->connect_error) {
                throw new MeekroDBException("Unable to connect to MySQL server! Error: {$mysql->connect_error}");
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
     * @see https://meekro.com/docs/misc-methods.html
     */
    public function disconnect()
    {
        $mysqli = $this->internal_mysql;
        if ($mysqli instanceof MySQLi) {
            if ($thread_id = $mysqli->thread_id) $mysqli->kill($thread_id);
            $mysqli->close();
        }
        $this->internal_mysql = null;
    }

    /**
     * You can add your own functions to run at several different stages of the query process.
     *
     * @see https://meekro.com/docs/hooks.html
     * @param string $type pre_parse, pre_run, post_run, run_success, run_failed
     * @param callable $fn
     * @return int hook_id
     * @throws MeekroDBException
     */
    public function addHook(string $type, callable $fn): int
    {
        if (!array_key_exists($type, $this->hooks)) {
            throw new MeekroDBException("Hook type $type is not recognized");
        }

        $this->hooks[$type][] = $fn;
        end($this->hooks[$type]);
        return key($this->hooks[$type]);
    }

    /**
     * Remove the hook. This expects the hook_id returned by DB::addHook().
     *
     * @param string $type pre_parse, pre_run, post_run, run_success, run_failed
     * @param int $index hook_id
     * @throws MeekroDBException
     */
    public function removeHook(string $type, int $index)
    {
        if (!array_key_exists($type, $this->hooks)) {
            throw new MeekroDBException("Hook type $type is not recognized");
        }

        if (!array_key_exists($index, $this->hooks[$type])) {
            throw new MeekroDBException("That hook does not exist");
        }

        unset($this->hooks[$type][$index]);
    }

    /**
     * Remove all hooks of a certain type.
     *
     * @param string $type pre_parse, pre_run, post_run, run_success, run_failed
     * @throws MeekroDBException
     */
    public function removeHooks(string $type)
    {
        if (!array_key_exists($type, $this->hooks)) {
            throw new MeekroDBException("Hook type $type is not recognized");
        }

        $this->hooks[$type] = array();
    }

    private function runHook(string $type, $args = array())
    {
        if (!array_key_exists($type, $this->hooks)) {
            throw new MeekroDBException("Hook type $type is not recognized");
        }

        if ($type == 'pre_parse') {
            $query = $args['query'];
            $args = $args['args'];

            foreach ($this->hooks[$type] as $hook) {
                $result = $hook(array('query' => $query, 'args' => $args));
                if (is_null($result)) {
                    $result = array($query, $args);
                }
                if (!is_array($result) || count($result) != 2) {
                    throw new MeekroDBException("pre_parse hook must return an array of 2 items");
                }
                if (!is_string($result[0])) {
                    throw new MeekroDBException("pre_parse hook must return a string as its first item");
                }
                if (!is_array($result[1])) {
                    throw new MeekroDBException("pre_parse hook must return an array as its second item");
                }

                $query = $result[0];
                $args = $result[1];
            }

            return array($query, $args);
        } else if ($type == 'pre_run') {
            $query = $args['query'];

            foreach ($this->hooks[$type] as $hook) {
                $result = $hook(array('query' => $query));
                if (is_null($result)) $result = $query;
                if (!is_string($result)) throw new MeekroDBException("pre_run hook must return a string");

                $query = $result;
            }

            return $query;
        } else if ($type == 'post_run') {
            foreach ($this->hooks[$type] as $hook) {
                $hook($args);
            }
        } else if ($type == 'run_success') {
            foreach ($this->hooks[$type] as $hook) {
                $hook($args);
            }
        } else if ($type == 'run_failed') {

            foreach ($this->hooks[$type] as $hook) {
                $result = $hook($args);
                if ($result === false) return false;
            }
        } else {
            throw new MeekroDBException("runHook() type $type not recognized");
        }

        return null;
    }

    private function defaultRunHook(array $args)
    {
        if (!$this->logfile) return;

        $query = $args['query'];
        $query = preg_replace('/\s+/', ' ', $query);
        $query = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $query);

        $results[] = sprintf('[%s]', date('Y-m-d H:i:s'));
        $results[] = sprintf('QUERY: %s', $query);
        $results[] = sprintf('RUNTIME: %s ms', $args['runtime']);

        if (isset($args['affected']) && $args['affected']) {
            $results[] = sprintf('AFFECTED ROWS: %s', $args['affected']);
        }
        if (isset($args['rows']) && $args['rows']) {
            $results[] = sprintf('RETURNED ROWS: %s', $args['rows']);
        }
        if (isset($args['error'])) {
            $results[] = 'ERROR: ' . $args['error'];
        }

        $results = implode("\n", $results) . "\n\n";

        if (is_resource($this->logfile)) {
            fwrite($this->logfile, $results);
        } else {
            file_put_contents($this->logfile, $results, FILE_APPEND);
        }
    }

    /**
     * @return string|null
     * @throws MeekroDBException
     */
    public function serverVersion(): ?string
    {
        $this->get();
        return $this->server_info;
    }

    /**
     * @return int
     */
    public function transactionDepth(): int
    {
        return $this->nested_transactions_count;
    }

    /**
     * Returns the auto incrementing ID for the last insert statement. The insert could have been done through DB::insert() or DB::query().
     *
     * @see https://meekro.com/docs/altering-data.html
     * @return int
     */
    public function insertId(): int
    {
        return $this->insert_id;
    }

    /**
     * Returns the number of rows changed by the last update statement. That statement could have been run through DB::update() or DB::query().
     *
     * @see https://meekro.com/docs/altering-data.html
     * @return int
     */
    public function affectedRows(): int
    {
        return $this->affected_rows;
    }

    /**
     * Counts the number of rows returned by the last query. Ignores queries done with DB::queryFirstRow() and DB::queryFirstField().
     *
     * @return int
     */
    public function numRows(): int
    {
        return $this->num_rows;
    }

    /**
     * Return the last query (whether or not it succeeded).
     *
     * @see https://meekro.com/docs/misc-methods.html
     * @return string|null
     */
    public function lastQuery(): ?string
    {
        return $this->last_query;
    }

    /**
     * Switch to a different database.
     *
     * @see https://meekro.com/docs/misc-methods.html
     * @param string $dbName
     * @throws MeekroDBException
     */
    public function useDB(string $dbName)
    {
        $db = $this->get();
        if (!$db->select_db($dbName)) throw new MeekroDBException("Unable to set database to $dbName");
        $this->current_db = $dbName;
    }

    /**
     * Normally, these three commands are just shortcuts for START TRANSACTION, COMMIT, and ROLLBACK.
     * When you enable DB::$nested_transactions, the transaction commands take on a special meaning. You can now use DB::startTransaction() within a transaction to "nest" transactions. This is accomplished internally with the SAVEPOINT feature in MySQL 5.5+.
     *
     * @see https://meekro.com/docs/transactions.html
     * @return int
     * @throws MeekroDBException
     */
    public function startTransaction(): int
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            throw new MeekroDBException("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
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
     * @see https://meekro.com/docs/transactions.html
     * @link startTransaction
     * @param bool $all
     * @return int
     * @throws MeekroDBException
     */
    public function commit(bool $all = false): int
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            throw new MeekroDBException("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
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
     * @see https://meekro.com/docs/transactions.html
     * @link startTransaction
     * @param bool $all
     * @return int
     * @throws MeekroDBException
     */
    public function rollback(bool $all = false): int
    {
        if ($this->nested_transactions && $this->serverVersion() < '5.5') {
            throw new MeekroDBException("Nested transactions are only available on MySQL 5.5 and greater. You are using MySQL " . $this->serverVersion());
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

    function formatTableName(string $table): string
    {
        $table = trim($table, '`');

        if (strpos($table, '.')) return implode('.', array_map(array($this, 'formatTableName'), explode('.', $table)));
        else return '`' . str_replace('`', '``', $table) . '`';
    }

    /**
     * Run an UPDATE command by specifying an array of changes to make, and a WHERE component. The WHERE component can have parameters in the same style as the query() command. As with insert() and replace(), you can use DB::sqleval() to pass a function directly to MySQL for evaluation.
     *
     * @see https://meekro.com/docs/altering-data.html
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function update()
    {
        $args = func_get_args();
        $table = array_shift($args);
        $params = array_shift($args);

        $update_part = $this->parse(
            str_replace('%', $this->param_char, "UPDATE %b SET %hc"),
            $table, $params
        );

        // we don't know if they used named or numbered args, so the where clause
        // must be run through the parser separately
        $where_part = $this->parse(...$args);
        $query = $update_part . ' WHERE ' . $where_part;
        return $this->query($query);
    }

    private function insertOrReplace($which, $table, $datas, $options = array())
    {
        $datas = unserialize(serialize($datas)); // break references within array
        $keys = $values = array();

        if (isset($datas[0]) && is_array($datas[0])) {
            $var = '%ll?';
            foreach ($datas as $datum) {
                ksort($datum);
                if (!$keys) $keys = array_keys($datum);
                $values[] = array_values($datum);
            }

        } else {
            $var = '%l?';
            $keys = array_keys($datas);
            $values = array_values($datas);
        }

        if ($which != 'INSERT' && $which != 'INSERT IGNORE' && $which != 'REPLACE') {
            throw new MeekroDBException('insertOrReplace() must be called with one of: INSERT, INSERT IGNORE, REPLACE');
        }

        if (isset($options['update']) && is_array($options['update']) && $options['update'] && $which == 'INSERT') {
            if (array_values($options['update']) !== $options['update']) {
                return $this->query(
                    str_replace('%', $this->param_char, "INSERT INTO %b %lb VALUES $var ON DUPLICATE KEY UPDATE %hc"),
                    $table, $keys, $values, $options['update']);
            } else {
                $update_str = array_shift($options['update']);
                $query_param = array(
                    str_replace('%', $this->param_char, "INSERT INTO %b %lb VALUES $var ON DUPLICATE KEY UPDATE ") . $update_str,
                    $table, $keys, $values);
                $query_param = array_merge($query_param, $options['update']);
                return $this->query(...$query_param);
            }

        }

        return $this->query(
            str_replace('%', $this->param_char, "%l INTO %b %lb VALUES $var"),
            $which, $table, $keys, $values);
    }

    /**
     * You can INSERT a row into a table. All you need is the table name and an assoc array to insert.
     * You may insert multiple rows at once by passing an array of associative arrays.
     * If you need to run SQL functions within your insert, you can wrap them in DB::sqleval() so they won't be escaped by MeekroDB.
     *
     * @see https://meekro.com/docs/altering-data.html
     * @param string $table
     * @param array $data
     * @return mixed
     * @throws MeekroDBException
     */
    public function insert(string $table, array $data)
    {
        return $this->insertOrReplace('INSERT', $table, $data);
    }

    /**
     * Works like INSERT, except it does an INSERT IGNORE. Won't give a MySQL error if the primary key is already taken.
     *
     * @see https://meekro.com/docs/altering-data.html
     * @param string $table
     * @param array $data
     * @return mixed
     * @throws MeekroDBException
     */
    public function insertIgnore(string $table, array $data)
    {
        return $this->insertOrReplace('INSERT IGNORE', $table, $data);
    }

    /**
     * Works just like INSERT, but does a REPLACE.
     *
     * @see https://meekro.com/docs/altering-data.html
     * @param string $table
     * @param array $data
     * @return mixed
     * @throws MeekroDBException
     */
    public function replace(string $table, array $data)
    {
        return $this->insertOrReplace('REPLACE', $table, $data);
    }

    /**
     * Does an INSERT ... ON DUPLICATE KEY UPDATE. In this first example, if the primary key is already taken then we just change the account's password to "hello."
     * We can also pass a second assoc array. If the primary key is already taken, the second assoc array will be used to update those fields. This example does the same thing as the last one.
     * If you just pass a single assoc array, it will update all of its values if the primary key is taken. This is similar to a REPLACE INTO. In this example, if the primary key is taken then both the username and password will be updated.
     *
     * @see https://meekro.com/docs/altering-data.html
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
                throw new MeekroDBException("Badly formatted insertUpdate() query -- you didn't specify the update component!");
            }

            $args[0] = $data;
        }

        if (is_array($args[0])) $update = $args[0];
        else $update = $args;

        return $this->insertOrReplace('INSERT', $table, $data, array('update' => $update));
    }

    /**
     * This command doesn't add much value over doing a DELETE with DB::query(), but it's here if you want it. In this example, the two commands are equivalent.
     *
     * @see https://meekro.com/docs/altering-data.html
     * @return mixed
     * @throws MeekroDBException
     */
    public function delete()
    {
        $args = func_get_args();
        $table = $this->formatTableName(array_shift($args));

        $where = $this->parse(...$args);
        $query = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($query);
    }

    public function sqleval(): MeekroDBEval
    {
        $args = func_get_args();
        $text = $this->parse(...$args);
        return new MeekroDBEval($text);
    }

    /**
     * Get an array of the columns in the requested table in the current database.
     *
     * @see https://meekro.com/docs/misc-methods.html
     * @param $table
     * @return array
     * @throws MeekroDBException
     */
    public function columnList($table): array
    {
        $data = $this->query("SHOW COLUMNS FROM %b", $table);
        $columns = array();
        foreach ($data as $row) {
            $columns[$row['Field']] = array(
                'type' => $row['Type'],
                'null' => $row['Null'],
                'key' => $row['Type'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            );
        }

        return $columns;
    }

    /**
     * Get an array of the tables in the requested database. If no database is specified, use the current database.
     *
     * @see https://meekro.com/docs/misc-methods.html
     * @param string|null $db
     * @return array
     * @throws MeekroDBException
     */
    public function tableList(?string $db = null): array
    {
        if ($db) {
            $olddb = $this->current_db;
            $this->useDB($db);
        }

        $result = $this->queryFirstColumn('SHOW TABLES');
        if (isset($olddb)) $this->useDB($olddb);
        return $result;
    }

    private function paramsMap()
    {
        $t = $this;

        return array(
            's' => function ($arg) use ($t) {
                return $t->escape($arg);
            },
            'i' => function ($arg) use ($t) {
                return $t->intval($arg);
            },
            'd' => function ($arg) use ($t) {
                return doubleval($arg);
            },
            'b' => function ($arg) use ($t) {
                return $t->formatTableName($arg);
            },
            'l' => function ($arg) use ($t) {
                return strval($arg);
            },
            't' => function ($arg) use ($t) {
                return $t->escapeTS($arg);
            },
            'ss' => function ($arg) use ($t) {
                return $t->escape("%" . str_replace(array('%', '_'), array('\%', '\_'), $arg) . "%");
            },

            'ls' => function ($arg) use ($t) {
                return array_map(array($t, 'escape'), $arg);
            },
            'li' => function ($arg) use ($t) {
                return array_map(array($t, 'intval'), $arg);
            },
            'ld' => function ($arg) use ($t) {
                return array_map('doubleval', $arg);
            },
            'lb' => function ($arg) use ($t) {
                return array_map(array($t, 'formatTableName'), $arg);
            },
            'll' => function ($arg) use ($t) {
                return array_map('strval', $arg);
            },
            'lt' => function ($arg) use ($t) {
                return array_map(array($t, 'escapeTS'), $arg);
            },

            '?' => function ($arg) use ($t) {
                return $t->sanitize($arg);
            },
            'l?' => function ($arg) use ($t) {
                return $t->sanitize($arg, 'list');
            },
            'll?' => function ($arg) use ($t) {
                return $t->sanitize($arg, 'doublelist');
            },
            'hc' => function ($arg) use ($t) {
                return $t->sanitize($arg, 'hash');
            },
            'ha' => function ($arg) use ($t) {
                return $t->sanitize($arg, 'hash', ' AND ');
            },
            'ho' => function ($arg) use ($t) {
                return $t->sanitize($arg, 'hash', ' OR ');
            },

            $this->param_char => function ($arg) use ($t) {
                return $t->param_char;
            },
        );
    }

    private function nextQueryParam($query)
    {
        $keys = array_keys($this->paramsMap());

        $first_position = PHP_INT_MAX;
        $first_param = null;
        $first_type = null;
        $arg = null;
        $named_arg = null;
        foreach ($keys as $key) {
            $fullkey = $this->param_char . $key;
            $pos = strpos($query, $fullkey);
            if ($pos === false) continue;

            if ($pos <= $first_position) {
                $first_position = $pos;
                $first_param = $fullkey;
                $first_type = $key;
            }
        }

        if (is_null($first_param)) return;

        $first_position_end = $first_position + strlen($first_param);
        $named_seperator_length = strlen($this->named_param_seperator);
        $arg_mask = '0123456789';
        $named_arg_mask = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';

        if ($arg_number_length = strspn($query, $arg_mask, $first_position_end)) {
            $arg = intval(substr($query, $first_position_end, $arg_number_length));
            $first_param = substr($query, $first_position, strlen($first_param) + $arg_number_length);
        } else if (substr($query, $first_position_end, $named_seperator_length) == $this->named_param_seperator) {
            $named_arg_length = strspn($query, $named_arg_mask, $first_position_end + $named_seperator_length);

            if ($named_arg_length > 0) {
                $named_arg = substr($query, $first_position_end + $named_seperator_length, $named_arg_length);
                $first_param = substr($query, $first_position, strlen($first_param) + $named_seperator_length + $named_arg_length);
            }
        }

        return array(
            'param' => $first_param,
            'type' => $first_type,
            'pos' => $first_position,
            'arg' => $arg,
            'named_arg' => $named_arg,
        );
    }

    private function preParse($query, $args)
    {
        $arg_ct = 0;
        $max_numbered_arg = 0;
        $use_numbered_args = false;
        $use_named_args = false;

        $queryParts = array();
        while ($Param = $this->nextQueryParam($query)) {
            if ($Param['pos'] > 0) {
                $queryParts[] = substr($query, 0, $Param['pos']);
            }

            if ($Param['type'] != $this->param_char && is_null($Param['arg']) && is_null($Param['named_arg'])) {
                $Param['arg'] = $arg_ct++;
            }

            if (!is_null($Param['arg'])) {
                $use_numbered_args = true;
                $max_numbered_arg = max($max_numbered_arg, $Param['arg']);
            }
            if (!is_null($Param['named_arg'])) {
                $use_named_args = true;
            }

            $queryParts[] = $Param;
            $query = substr($query, $Param['pos'] + strlen($Param['param']));
        }

        if (strlen($query) > 0) {
            $queryParts[] = $query;
        }

        if ($use_named_args) {
            if ($use_numbered_args) {
                throw new MeekroDBException("You can't mix named and numbered args!");
            }

            if (count($args) != 1 || !is_array($args[0])) {
                throw new MeekroDBException("If you use named args, you must pass an assoc array of args!");
            }
        }

        if ($use_numbered_args) {
            if ($max_numbered_arg + 1 > count($args)) {
                throw new MeekroDBException(sprintf('Expected %d args, but only got %d!', $max_numbered_arg + 1, count($args)));
            }
        }

        return $queryParts;
    }

    private function parse($query)
    {
        $args = func_get_args();
        array_shift($args);
        $query = trim($query);

        if (!$args) return $query;
        $queryParts = $this->preParse($query, $args);

        $array_types = array('ls', 'li', 'ld', 'lb', 'll', 'lt', 'l?', 'll?', 'hc', 'ha', 'ho');
        $Map = $this->paramsMap();
        $query = '';
        foreach ($queryParts as $Part) {
            if (is_string($Part)) {
                $query .= $Part;
                continue;
            }

            $fn = $Map[$Part['type']];
            $is_array_type = in_array($Part['type'], $array_types, true);

            $val = null;
            if (!is_null($Part['named_arg'])) {
                $key = $Part['named_arg'];
                if (!array_key_exists($key, $args[0])) {
                    throw new MeekroDBException("Couldn't find named arg {$key}!");
                }

                $val = $args[0][$key];
            } else if (!is_null($Part['arg'])) {
                $key = $Part['arg'];
                $val = $args[$key];
            }

            if ($is_array_type && !is_array($val)) {
                throw new MeekroDBException("Expected an array for arg $key but didn't get one!");
            }
            if ($is_array_type && count($val) == 0) {
                throw new MeekroDBException("Arg {$key} array can't be empty!");
            }
            if (!$is_array_type && is_array($val)) {
                $val = '';
            }

            if (is_object($val) && ($val instanceof WhereClause)) {
                if ($Part['type'] != 'l') {
                    throw new MeekroDBException("WhereClause must be used with l arg, you used {$Part['type']} instead!");
                }

                list($clause_sql, $clause_args) = $val->textAndArgs();
                array_unshift($clause_args, $clause_sql);
                $result = $this->parse(...$clause_args);
            } else {
                $result = $fn($val);
                if (is_array($result)) $result = '(' . implode(',', $result) . ')';
            }

            $query .= $result;
        }

        return $query;
    }

    private function escape($str)
    {
        return "'" . $this->get()->real_escape_string(strval($str)) . "'";
    }

    private function sanitize($value, $type = 'basic', $hashjoin = ', ')
    {
        if ($type == 'basic') {
            if (is_object($value)) {
                if ($value instanceof MeekroDBEval) return $value->text;
                else if ($value instanceof DateTime) return $this->escape($value->format('Y-m-d H:i:s'));
                else return $this->escape($value); // use __toString() value for objects, when possible
            }

            if (is_null($value)) return 'NULL';
            else if (is_bool($value)) return ($value ? 1 : 0);
            else if (is_int($value)) return $value;
            else if (is_float($value)) return $value;
            else if (is_array($value)) return "''";
            else return $this->escape($value);

        } else if ($type == 'list') {
            if (is_array($value)) {
                $value = array_values($value);
                return '(' . implode(', ', array_map(array($this, 'sanitize'), $value)) . ')';
            } else {
                throw new MeekroDBException("Expected array parameter, got something different!");
            }
        } else if ($type == 'doublelist') {
            if (is_array($value) && array_values($value) === $value && is_array($value[0])) {
                $cleanvalues = array();
                foreach ($value as $subvalue) {
                    $cleanvalues[] = $this->sanitize($subvalue, 'list');
                }
                return implode(', ', $cleanvalues);

            } else {
                throw new MeekroDBException("Expected double array parameter, got something different!");
            }
        } else if ($type == 'hash') {
            if (is_array($value)) {
                $pairs = array();
                foreach ($value as $k => $v) {
                    $pairs[] = $this->formatTableName($k) . '=' . $this->sanitize($v);
                }

                return implode($hashjoin, $pairs);
            } else {
                throw new MeekroDBException("Expected hash (associative array) parameter, got something different!");
            }
        } else {
            throw new MeekroDBException("Invalid type passed to sanitize()!");
        }

    }

    private function escapeTS($ts)
    {
        if (is_string($ts)) {
            $str = date('Y-m-d H:i:s', strtotime($ts));
        } else if (is_object($ts) && ($ts instanceof DateTime)) {
            $str = $ts->format('Y-m-d H:i:s');
        }

        return $this->escape($str);
    }

    private function intval($var)
    {
        if (PHP_INT_SIZE == 8) return intval($var);
        return floor(doubleval($var));
    }

    /**
     * You pass a MySQL query string with placeholder values, followed by a parameter for every placeholder. Results are returned as an array of assoc arrays. If there are no results, this function returns an empty array.
     * You can refer to parameters out of order by adding a number to each placeholder. You can even refer to the same parameter more than once.
     * You can pass an array of named parameters and access them by name.
     * You can use a placeholder to represent a list of strings or integers, for use with IN or NOT IN.
     *
     * @link https://meekro.com/docs/retrieving-data.html
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function query()
    {
        return $this->queryHelper(array('assoc' => true), func_get_args());
    }

    /**
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function queryAllLists()
    {
        return $this->queryHelper(array(), func_get_args());
    }

    /**
     * Like DB::query(), except the keys for each associative array will be in the form TableName.ColumnName. Useful if you're joining several tables, and they each have an id field.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function queryFullColumns()
    {
        return $this->queryHelper(array('fullcols' => true), func_get_args());
    }

    /**
     * This is for situations where you need to return a large dataset that can't be read into memory all at once. It lets you iterate through the results as shown below.
     * If you don't read all of the results, you must use free() before running any other queries! Otherwise your next query will fail.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function queryWalk()
    {
        return $this->queryHelper(array('walk' => true), func_get_args());
    }

    /**
     * @param $opts
     * @param $args
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    private function queryHelper($opts, $args)
    {
        $query = array_shift($args);

        $opts_fullcols = (isset($opts['fullcols']) && $opts['fullcols']);
        $opts_raw = (isset($opts['raw']) && $opts['raw']);
        $opts_assoc = (isset($opts['assoc']) && $opts['assoc']);
        $opts_walk = (isset($opts['walk']) && $opts['walk']);
        $is_buffered = !($opts_raw || $opts_walk);

        list($query, $args) = $this->runHook('pre_parse', array('query' => $query, 'args' => $args));
        $invokeArgs = array_merge(array($query), $args);
        $sql = $this->parse(...$invokeArgs);
        $sql = $this->runHook('pre_run', array('query' => $sql));
        $this->last_query = $sql;

        $db = $this->get();
        $starttime = microtime(true);
        $result = $db->query($sql, $is_buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT);
        $runtime = microtime(true) - $starttime;
        $runtime = sprintf('%f', $runtime * 1000);

        $this->insert_id = $db->insert_id;
        $this->affected_rows = $db->affected_rows;

        // mysqli_result->num_rows won't initially show correct results for unbuffered data
        if ($is_buffered && ($result instanceof MySQLi_Result)) $this->num_rows = $result->num_rows;
        else $this->num_rows = null;

        $Exception = null;
        if ($db->error) {
            $Exception = new MeekroDBException($db->error, $sql, $db->errno);
        }

        $hookHash = array('query' => $sql, 'runtime' => $runtime);
        if ($Exception) {
            $hookHash['exception'] = $Exception;
            $hookHash['error'] = $Exception->getMessage();
        } else if ($this->num_rows) {
            $hookHash['rows'] = $this->num_rows;
        } else {
            $hookHash['affected'] = $db->affected_rows;
        }

        $this->defaultRunHook($hookHash);
        $this->runHook('post_run', $hookHash);
        if ($Exception) {
            $result = $this->runHook('run_failed', $hookHash);
            if ($result !== false) throw $Exception;
        } else {
            $this->runHook('run_success', $hookHash);
        }

        if ($opts_walk) return new MeekroDBWalk($db, $result);
        if (!($result instanceof MySQLi_Result)) return $result; // query was not a SELECT?
        if ($opts_raw) return $result;

        $return = array();

        if ($opts_fullcols) {
            $infos = array();
            foreach ($result->fetch_fields() as $info) {
                if (strlen($info->table)) $infos[] = $info->table . '.' . $info->name;
                else $infos[] = $info->name;
            }
        }

        while ($row = ($opts_assoc ? $result->fetch_assoc() : $result->fetch_row())) {
            if ($opts_fullcols) $row = array_combine($infos, $row);
            $return[] = $row;
        }

        // free results
        $result->free();
        while ($db->more_results()) {
            $db->next_result();
            if ($result = $db->use_result()) $result->free();
        }

        return $return;
    }

    /**
     * Retrieve the first row of results for the query, and return it as an associative array. If the query returned no rows, this returns null.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return false|mixed|null
     * @throws MeekroDBException
     */
    public function queryFirstRow()
    {
        $args = func_get_args();
        $result = $this->query(...$args);
        if (!$result || !is_array($result)) return null;
        return reset($result);
    }

    /**
     * Retrieve the first row of results for the query, and return it as a numbered index (non-associative) array. If the query returned no rows, this returns null.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return false|mixed|null
     * @throws MeekroDBException
     */
    public function queryFirstList()
    {
        $args = func_get_args();
        $result = $this->queryAllLists(...$args);
        if (!$result || !is_array($result)) return null;
        return reset($result);
    }

    /**
     * Retrieve the first column of results for the query, and return it as a regular array. If the query returned no rows, this returns an empty array.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return array
     * @throws MeekroDBException
     */
    public function queryFirstColumn(): array
    {
        $args = func_get_args();
        $results = $this->queryAllLists(...$args);
        $ret = array();

        if (!count($results) || !count($results[0])) return $ret;

        foreach ($results as $row) {
            $ret[] = $row[0];
        }

        return $ret;
    }

    /**
     * Get the contents of the first field from the first row of results, and return that. If no rows were returned by the query, this returns null.
     *
     * @see https://meekro.com/docs/retrieving-data.html
     * @return mixed
     * @throws MeekroDBException
     */
    public function queryFirstField()
    {
        $args = func_get_args();
        $row = $this->queryFirstList(...$args);
        if ($row == null) return null;
        return $row[0];
    }

    // --- begin deprecated methods (kept for backwards compatability)
    public function debugMode($enable = true)
    {
        if ($enable) $this->logfile = fopen('php://output', 'w');
        else $this->logfile = null;
    }

    /**
     * @return array|bool|MeekroDBWalk|mysqli_result
     * @throws MeekroDBException
     */
    public function queryRaw()
    {
        return $this->queryHelper(array('raw' => true), func_get_args());
    }

    /**
     * @return false|mixed|null
     * @throws MeekroDBException
     */
    public function queryOneList()
    {
        $args = func_get_args();
        return $this->queryFirstList(...$args);
    }

    /**
     * @return false|mixed|null
     * @throws MeekroDBException
     */
    public function queryOneRow()
    {
        $args = func_get_args();
        return $this->queryFirstRow(...$args);
    }

    /**
     * @return mixed|null
     * @throws MeekroDBException
     */
    public function queryOneField()
    {
        $args = func_get_args();
        $column = array_shift($args);

        $row = $this->queryOneRow(...$args);
        if ($row == null) {
            return null;
        } else if ($column === null) {
            $keys = array_keys($row);
            $column = $keys[0];
        }

        return $row[$column];
    }

    /**
     * @see https://meekro.com/docs/retrieving-data.html
     * @return array
     * @throws MeekroDBException
     */
    public function queryOneColumn(): array
    {
        $args = func_get_args();
        $column = array_shift($args);
        $results = $this->query(...$args);
        $ret = array();

        if (!count($results) || !count($results[0])) return $ret;
        if ($column === null) {
            $keys = array_keys($results[0]);
            $column = $keys[0];
        }

        foreach ($results as $row) {
            $ret[] = $row[$column];
        }

        return $ret;
    }

}

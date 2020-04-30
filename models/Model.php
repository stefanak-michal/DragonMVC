<?php

namespace models;

use core\Config;
use helpers\ArrayUtil;

/**
 * Model
 *
 * Base database model with CRUD actions for extending
 */
abstract class Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table;
    /**
     * Primary key column
     *
     * @var string
     */
    protected $primary_key;

    /**
     * MeekroDB
     *
     * @var \MeekroDB\MeekroDB
     */
    protected static $db;
	
	/**
	 * @var int
	 */
	private static $instanceCount = 0;

    /**
     * Construct
     */
    public function __construct()
    {
		self::$instanceCount++;
		
        if (empty(self::$db)) {
            self::$db = new \MeekroDB\MeekroDB(
                Config::gi()->get('dbServer'),
                Config::gi()->get('dbUser'),
                Config::gi()->get('dbPass'),
                Config::gi()->get('dbDatabase'),
                Config::gi()->get('dbPort')
            );

            if (!IS_WORKSPACE) {
                self::setDatabaseErrorHandlers();
            } else {
                self::$db->success_handler = function ($args) {
                    \core\Debug::query($args['query'], $args['explain'] ?? [], array_intersect_key($args, array_flip(['runtime', 'affected'])));
                };
            }
        }

        if (empty($this->table)) {
            $parts = explode("\\", get_class($this));
            $this->table = trim(preg_replace_callback("/[A-Z]/", function ($item) {
                return '_' . strtolower($item[0]);
            }, end($parts)), '_');
        }
    }

    /**
     * Custom production database error handlers
     */
    private static function setDatabaseErrorHandlers()
    {
        self::$db->error_handler = function ($params) {
            /* @var $e Exception */
            $e = new Exception();
            $backtrace = preg_split("/[\r\n]+/", $e->getTraceAsString());

            //remove core traces
            foreach ($backtrace as $key => $line) {
                if (strpos($line, 'internal function') || strpos($line, 'DB.php')) {
                    unset($backtrace[$key]);
                } else {
                    break;
                }
            }

            //remove trace auto increment
            foreach ($backtrace as &$line) {
                $line = preg_replace("/^#\d+ /", '', $line);
            }

            $backtrace = array_slice($backtrace, 0, -2);

            trigger_error(implode(PHP_EOL, $params) . PHP_EOL . implode(PHP_EOL, $backtrace), E_USER_WARNING);
        };

        self::$db->nonsql_error_handler = function ($params) {
            trigger_error(implode(PHP_EOL, $params), E_USER_WARNING);
            header("HTTP/1.1 500 Internal Server Error");
            readfile(BASE_PATH . DS . '500.html');
            exit;
        };
    }

    /**
     * Insert new row
     *
     * @param array $data
     * @return int
     */
    public function create($data)
    {
        self::$db->insert($this->table, $data);
        return self::$db->insertId();
    }

    /**
     * Base reading data from table
     *
     * @param array|int $ids
     * @return array
     */
    public function read($ids = [])
    {
        $output = [];

        if (empty($ids)) {
            $output = self::$db->query('SELECT * FROM ' . $this->table);
        } elseif (is_numeric($ids) || (is_array($ids) && count($ids) == 1)) {
            $output = self::$db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', is_numeric($ids) ? $ids : reset($ids));
        } elseif (is_array($ids)) {
            $output = self::$db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
        }

        return ArrayUtil::reIndex($output, $this->primary_key);
    }

    /**
     * @param int $id
     * @return array
     */
    public function row(int $id): array
    {
        if (empty($id)) {
            return [];
        }

        return (array) self::$db->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', $id);
    }

    /**
     * Update row
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, $data)
    {
        self::$db->update($this->table, $data, $this->primary_key . ' = %i', $id);
        return self::$db->affectedRows();
    }

    /**
     * Delete row by primary key
     *
     * @param int $id
     */
    public function delete($id)
    {
        if (is_numeric($id)) {
            self::$db->delete($this->table, $this->primary_key . ' = %i', $id);
        }
    }

    /**
     * Destruktor
     */
    public function __destruct()
    {
        self::$instanceCount--;
        if (self::$instanceCount == 0)
            self::$db->disconnect();
    }
}

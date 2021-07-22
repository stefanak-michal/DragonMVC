<?php

namespace models;

use core\Config;
use MeekroDB\MeekroDBException;

/**
 * Model
 * 
 * Base database model with CRUD actions for extending
 */
abstract class AModel
{
    /**
     * Table name
     * @var string
     */
    protected $table;

    /**
     * Primary key column
     * @var string
     */
    protected $primary_key;

    /**
     * List of table columns
     *
     * @var array
     */
    protected $columns;

    /**
     * MeekroDB
     * @var \MeekroDB\MeekroDB[] [configKey => \MeekroDB\MeekroDB]
     */
    private static $db = [];

    /**
     * @var array [className => [column]]
     */
    private static $cachedColumns = [];

    /**
     * @var array [configKey => [table]]
     */
    private static $tables = [];

    /**
     * @var string
     */
    private $configKey;

    /**
     * Construct
     * @param string $configKey
     */
    public function __construct(string $configKey = 'mysql')
    {
        $this->configKey = $configKey;

        if (!array_key_exists($configKey, self::$db)) {
            self::$db[$configKey] = new \MeekroDB\MeekroDB;
            \core\Config::apply($configKey, self::$db[$configKey]);

            if (!IS_WORKSPACE) {
                $this->setDatabaseErrorHandlers();
            } else {
                $this->db()->success_handler = function ($args) {
                    \core\Debug::query($args['query'], $args['explain'] ?? [], [
                        'params' => null,
                        'stats' => '<pre><b>rows:</b> ' . $args['affected'] . '</pre>',
                        'time (ms)' => $args['runtime']
                    ]);
                };
            }
        }

        if (!array_key_exists($configKey, self::$tables)) {
            self::$tables[$configKey] = $this->db()->tableList();
        }

        $this->setTableName();

        if (empty($this->columns)) {
            if (empty(self::$cachedColumns[get_class($this)])) {
                self::$cachedColumns[get_class($this)] = $this->db()->columnList($this->table);
            }
            $this->columns = self::$cachedColumns[get_class($this)];
        }
    }

    /**
     * Build table name by class name or full namespace name
     */
    private function setTableName()
    {
        if (!empty($this->table))
            return;

        $ns = trim(get_class($this), "\\");
        $parts = explode("\\", $ns);
        array_shift($parts);
        $parts = array_map('ucfirst', $parts);

        $table = \helpers\Utils::snake_case(implode($parts));
        if (in_array($table, self::$tables[$this->configKey])) {
            $this->table = $table;
            return;
        }

        $table = \helpers\Utils::snake_case(array_pop($parts));
        if (in_array($table, self::$tables[$this->configKey])) {
            $this->table = $table;
            return;
        }

        trigger_error('Missing table name in model class ' . get_class($this), E_USER_WARNING);
    }

    /**
     * Get database instance
     * @return \MeekroDB\MeekroDB
     */
    protected function db(): \MeekroDB\MeekroDB
    {
        return self::$db[$this->configKey];
    }

    /**
     * Custom production database error handlers
     */
    private function setDatabaseErrorHandlers()
    {
        $this->db()->error_handler = function ($params) {
            $e = new \Exception();
            $backtrace = preg_split("/[\r\n]+/", strip_tags($e->getTraceAsString()));

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

        $this->db()->nonsql_error_handler = function ($params) {
            file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'mariadb.log', var_export($params, true), FILE_APPEND);
            http_response_code(500);
            echo 'We are sorry. Database error occured.';
            exit;
        };
    }

    /**
     * Insert new row
     * @param array $data
     * @return int New created primary key
     */
    public function create(array $data): int
    {
        //auto filter array vs columns of table
        if (is_array(reset($data))) {
            foreach ($data as &$entry) {
                $entry = array_intersect_key($entry, array_flip($this->columns));
            }
        } else {
            $data = array_intersect_key($data, array_flip($this->columns));
        }

        if (!empty($data)) {
            $this->db()->insert($this->table, $data);
            return $this->db()->insertId();
        }

        return 0;
    }

    /**
     * Get rows
     * @param array $ids
     * @return array
     */
    public function read(array $ids = []): array
    {
        $output = array();

        if (empty($ids)) {
            $output = $this->db()->query('SELECT * FROM ' . $this->table);
        } elseif (count($ids) == 1) {
            $output[] = $this->row(reset($ids));
        } else {
            $output = $this->db()->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
        }

        return \helpers\ArrayUtils::reIndex($output, $this->primary_key);
    }

    /**
     * Get row by primary key
     * @param int $id
     * @return array
     */
    public function row(int $id): array
    {
        if (empty($id)) {
            return [];
        }
        return $this->db()->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', $id) ?? [];
    }

    /**
     * Update row
     * @param int $id
     * @param array $data
     * @return int Affected rows
     */
    public function update(int $id, array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->columns));

        if (!empty($data)) {
            $this->db()->update($this->table, $data, $this->primary_key . ' = %i', $id);
            return $this->db()->affectedRows();
        }

        return 0;
    }

    /**
     * InsertUpdate row
     * @param array $data
     */
    public function replace(array $data)
    {
        $data = array_intersect_key($data, array_flip($this->columns));
        if (!empty($data)) {
            try {
                $this->db()->insertUpdate($this->table, $data);
            } catch (MeekroDBException $e) {
                \core\Debug::var_dump($e->getMessage());
            }
        }
    }

    /**
     * Delete row by primary key
     * @param int $id
     */
    public function delete(int $id)
    {
        $this->db()->delete($this->table, $this->primary_key . ' = %i', $id);
    }

    /**
     * Start transaction
     * @return bool
     */
    public function startTransaction(): bool
    {
        try {
            $this->db()->startTransaction();
            return true;
        } catch (MeekroDBException $e) {
            return false;
        }
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit(): bool
    {
        try {
            $this->db()->commit();
            return true;
        } catch (MeekroDBException $e) {
            return false;
        }
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback(): bool
    {
        try {
            $this->db()->rollback();
            return true;
        } catch (MeekroDBException $e) {
            return false;
        }
    }

}

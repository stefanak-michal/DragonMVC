<?php

namespace models;

use MeekroDB\MeekroDB;
use MeekroDB\MeekroDBException;

/**
 * AModel
 * Base database model with CRUD actions for extending
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 * @package models
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
     * @var MeekroDB[] [configKey => MeekroDB]
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
     * @throws MeekroDBException
     */
    public function __construct(string $configKey = 'mysql')
    {
        $this->configKey = $configKey;

        if (!array_key_exists($configKey, self::$db)) {
            $args = [];
            $config = \core\Config::gi()->get($configKey);
            $reflection = new \ReflectionClass(MeekroDB::class);
            foreach ($reflection->getConstructor()->getParameters() as $parameter) {
                $args[] = array_key_exists($parameter->getName(), $config) ? $config[$parameter->getName()] : null;
            }

            self::$db[$configKey] = new MeekroDB(...$args);

            if (IS_WORKSPACE) {
                try {
                    $this->db()->addHook('post_run', function ($args) {
                        $explain = [];

                        $k = strtoupper(explode(' ', $args['query'])[0]);
                        if ($k == 'EXPLAIN')
                            return;
                        if (in_array($k, ['SELECT', 'INSERT', 'DELETE', 'UPDATE']))
                            $explain = $this->db()->query('EXPLAIN ' . $args['query']);

                        \core\Debug::query($args['query'], $explain, [
                            'params' => null,
                            'stats' => '<pre><b>rows:</b> ' . ($args['rows'] ?? 0) . '</pre>',
                            'time (ms)' => $args['runtime'],
                            'database' => $this->db()->getCurrentDB()
                        ]);
                    });
                } catch (MeekroDBException $e) {
                    $this->errorHandler($e);
                }
            }
        }

        if (!array_key_exists($configKey, self::$tables)) {
            self::$tables[$configKey] = $this->db()->tableList();
        }

        $this->setTableName();

        if (empty($this->columns)) {
            if (empty(self::$cachedColumns[get_class($this)])) {
                self::$cachedColumns[get_class($this)] = array_keys($this->db()->columnList($this->table));
            }
            $this->columns = self::$cachedColumns[get_class($this)];

            if (empty($this->primary_key))
                $this->primary_key = reset($this->columns);
        }
    }

    /**
     * @param MeekroDBException $e
     */
    protected function errorHandler(MeekroDBException $e)
    {
        if (IS_WORKSPACE) {
            \core\Debug::var_dump($e->getMessage());
        } else {
            file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'error.log', '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            echo 'We are sorry. Database error occured.';
            exit;
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
     * @return MeekroDB
     */
    protected function db(): MeekroDB
    {
        return self::$db[$this->configKey];
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
            try {
                $this->db()->insert($this->table, $data);
                return $this->db()->insertId();
            } catch (MeekroDBException $e) {
                $this->errorHandler($e);
            }
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
            $output = $this->query('SELECT * FROM ' . $this->table);
        } elseif (count($ids) == 1) {
            $output[] = $this->row(reset($ids));
        } else {
            $output = $this->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
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
        return $this->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', $id);
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
        $affected = 0;

        try {
            $this->db()->update($this->table, $data, $this->primary_key . ' = %i', $id);
            $affected = $this->db()->affectedRows();
        } catch (MeekroDBException $e) {
            $this->errorHandler($e);
        }

        return $affected;
    }

    /**
     * InsertUpdate row
     * @param array $data
     */
    public function replace(array $data)
    {
        $data = array_intersect_key($data, array_flip($this->columns));
        try {
            if (!empty($data))
                $this->db()->insertUpdate($this->table, $data);
        } catch (MeekroDBException $e) {
            $this->errorHandler($e);
        }
    }

    /**
     * Delete row by primary key
     * @param int $id
     */
    public function delete(int $id)
    {
        try {
            $this->db()->delete($this->table, $this->primary_key . ' = %i', $id);
        } catch (MeekroDBException $e) {
            $this->errorHandler($e);
        }
    }

    /**
     * Execute query
     * @param string $query
     * @param array $params
     * @return array
     */
    public function query(string $query, array $params = []): array
    {
        $result = null;
        try {
            $result = $this->db()->query($query, $params);
        } catch (MeekroDBException $e) {
            $this->errorHandler($e);
        }
        return $result ?? [];
    }

    /**
     * Execute query and get first row from result
     * @param string $query
     * @param array $params
     * @return array
     */
    public function queryFirstRow(string $query, array $params = []): array
    {
        $result = null;
        try {
            $result = $this->db()->queryFirstRow($query, $params);
        } catch (MeekroDBException $e) {
            $this->errorHandler($e);
        }
        return $result ?? [];
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

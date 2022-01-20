<?php

namespace models;

if (!class_exists("\Bolt\Bolt"))
    trigger_error('Neo4j wrapper needs Bolt library from https://github.com/neo4j-php/Bolt', E_USER_ERROR);

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Exception;

/**
 * Class for Neo4j bolt driver
 * Wrapper for Bolt to cover basic functionality
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 * @link https://github.com/neo4j-php/Bolt
 */
class Neo4j
{
    /**
     * @var AProtocol
     */
    private static $protocol;

    /**
     * @var array
     */
    private static $statistics;

    /**
     * Assigned handler is called every time query is executed
     * @var callable (string $query, array $params = [], int $executionTime = 0, array $statistics = [])
     */
    public static $logHandler;

    /**
     * Provided handler is invoked on Exception instead of trigger_error
     * @var callable (Exception $e)
     */
    public static $errorHandler;

    /**
     * Get connection protocol for bolt communication
     * @return AProtocol
     */
    protected static function getProtocol(): AProtocol
    {
        if (!(self::$protocol instanceof AProtocol)) {
            try {
                $ssl = \core\Config::gi()->get('neo4j_ssl');
                if (empty($ssl)) {
                    $conn = new \Bolt\connection\Socket(
                        \core\Config::gi()->get('neo4j_ip', '127.0.0.1'),
                        \core\Config::gi()->get('neo4j_port', 7687)
                    );
                } else {
                    $conn = new \Bolt\connection\StreamSocket(
                        \core\Config::gi()->get('neo4j_ip', '127.0.0.1'),
                        \core\Config::gi()->get('neo4j_port', 7687)
                    );
                    $conn->setSslContextOptions($ssl);
                }

                $bolt = new Bolt($conn);
                self::$protocol = $bolt->build();
                self::$protocol->hello(\Bolt\helpers\Auth::basic(
                    \core\Config::gi()->get('neo4j_user'),
                    \core\Config::gi()->get('neo4j_pass')
                ));

                register_shutdown_function(function () {
                    try {
                        if (method_exists(self::$protocol, 'goodbye'))
                            self::$protocol->goodbye();
                    } catch (Exception $e) {
                    }
                });
            } catch (Exception $e) {
                self::handleException($e);
            }
        }

        return self::$protocol;
    }

    /**
     * Return full output
     * @param string $query
     * @param array $params
     * @return array
     */
    public static function query(string $query, array $params = []): array
    {
        self::checkParams($params);
        self::updateQueryParameters($query, $params);

        $run = $all = null;
        try {
            $run = self::getProtocol()->run($query, $params);
            $all = self::getProtocol()->pull();
        } catch (Exception $e) {
            self::handleException($e);
        }
        $last = array_pop($all);

        self::$statistics = $last['stats'] ?? [];
        self::$statistics['rows'] = count($all);

        if (is_callable(self::$logHandler)) {
            call_user_func(self::$logHandler, $query, $params, $run['t_first'] + $last['t_last'], self::$statistics);
        }

        return !empty($all) ? array_map(function ($element) use ($run) {
            return array_combine($run['fields'], $element);
        }, $all) : [];
    }


    /**
     * Check and update query parameters
     * @param array $params
     */
    private static function checkParams(array &$params)
    {
        foreach ($params as &$param) {
            if (is_array($param)) {
                self::checkParams($param);
            } elseif (is_string($param)) {
                if (strlen($param) == 0) {
                    $param = null;
                    continue;
                }

                $tmp = filter_var($param, FILTER_VALIDATE_INT, ['flags' => null]);
                if ($tmp !== false) {
                    $param = $tmp;
                    continue;
                }

                $tmp = filter_var($param, FILTER_VALIDATE_FLOAT);
                if ($tmp !== false) {
                    $param = $tmp;
                    continue;
                }
            }
        }
    }

    /**
     * From neo4j version 4 query parameters changed
     * @param string $query
     * @param array $params
     * @return void
     */
    private static function updateQueryParameters(string &$query, array $params)
    {
        if (version_compare(self::getProtocol()->getVersion(), 4, '>=')) {
            $query = str_replace(
                array_map(function ($a) {
                    return '{' . $a . '}';
                }, array_keys($params)),
                array_map(function ($a) {
                    return '$' . $a;
                }, array_keys($params)),
                $query
            );
        } else {
            $query = str_replace(
                array_map(function ($a) {
                    return '$' . $a;
                }, array_keys($params)),
                array_map(function ($a) {
                    return '{' . $a . '}';
                }, array_keys($params)),
                $query
            );
        }
    }

    /**
     * Get first value from first row
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public static function queryFirstField(string $query, array $params = [])
    {
        $data = self::query($query, $params);
        if (empty($data)) {
            return null;
        }
        return reset($data[0]);
    }

    /**
     * Get first values from all rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public static function queryFirstColumn(string $query, array $params = []): array
    {
        $data = self::query($query, $params);
        if (empty($data)) {
            return [];
        }
        $key = key($data[0]);
        return array_map(function ($element) use ($key) {
            return $element[$key];
        }, $data);
    }

    /**
     * Begin transaction
     * @return bool
     */
    public static function begin(): bool
    {
        try {
            self::getProtocol()->begin();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'BEGIN TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public static function commit(): bool
    {
        try {
            self::getProtocol()->commit();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'COMMIT TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public static function rollback(): bool
    {
        try {
            self::getProtocol()->rollback();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'ROLLBACK TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Return statistic info from last executed query
     *
     * Possible keys:
     * <pre>
     * nodes-created
     * nodes-deleted
     * properties-set
     * relationships-created
     * relationship-deleted
     * labels-added
     * labels-removed
     * indexes-added
     * indexes-removed
     * constraints-added
     * constraints-removed
     * </pre>
     *
     * @param string $key
     * @return int
     */
    public static function statistic(string $key): int
    {
        return intval(self::$statistics[$key] ?? 0);
    }

    /**
     * @param Exception $e
     */
    private static function handleException(Exception $e)
    {
        if (is_callable(self::$errorHandler)) {
            call_user_func(self::$errorHandler, $e);
            return;
        }

        trigger_error('Database error occured: ' . $e->getMessage() . ' ' . $e->getCode(), E_USER_ERROR);
    }

}

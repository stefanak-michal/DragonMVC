<?php

namespace core;

use \Exception;

/**
 * Framework
 * 
 * Base class of MVC framework
 */
final class Dragon
{

    /**
     * Called controller
     * 
     * @static
     * @var string
     */
    public static $controller;

    /**
     * Called method
     *
     * @static
     * @var string
     */
    public static $method;

    /**
     * Host name
     *
     * @var string 
     */
    public static $host;

    /**
     * Config
     *
     * @var Config
     */
    private $config;

    /**
     * Router
     *
     * @var Router
     */
    private $router;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->router = new Router($this->config);

        //we need database config
        DB::$host = $this->config->get('dbServer');
        DB::$user = $this->config->get('dbUser');
        DB::$password = $this->config->get('dbPass');
        DB::$dbName = $this->config->get('dbDatabase');

        //on production custom database error handler
        if ( !IS_WORKSPACE ) {
            $this->setDatabaseErrorHandlers();
        } else {
            DB::$success_handler = array("core\\Debug", 'query');
        }

        self::$host = $this->config->get('project_host');
    }

    /**
     * Custom production database error handlers
     */
    private function setDatabaseErrorHandlers()
    {
        DB::$error_handler = function($params) {
            /* @var $e Exception */
            $e = new Exception();
            $backtrace = preg_split("/[\r\n]+/", $e->getTraceAsString());

            //remove core traces
            foreach ( $backtrace AS $key => $line ) {
                if ( strpos($line, 'internal function') || strpos($line, 'DB.php') ) {
                    unset($backtrace[$key]);
                } else {
                    break;
                }
            }

            //remove trace auto increment
            foreach ( $backtrace AS &$line ) {
                $line = preg_replace("/^#\d+ /", '', $line);
            }

            $backtrace = array_slice($backtrace, 0, -2);

            trigger_error(implode(PHP_EOL, $params) . PHP_EOL . implode(PHP_EOL, $backtrace), E_USER_WARNING);
        };

        DB::$nonsql_error_handler = function($params) {
            trigger_error(implode(PHP_EOL, $params), E_USER_WARNING);
            header("HTTP/1.1 500 Internal Server Error");
            readfile(BASE_PATH . DS . '500.html');
            exit;
        };
    }

    /**
     * Vrati core Config
     * 
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Vrati core Router
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Run a project
     */
    public function run()
    {
        $cmv = array(
            'controller' => $this->config->get('defaultController'),
            'method' => $this->config->get('defaultMethod'),
            'vars' => array()
        );

        $_uri = new URI();
        $_uri->_fetch_uri_string();
        $path = (string) $_uri;

        if ( !empty($path) ) {
            $founded = $this->router->findRoute($path);
            if ( !empty($founded) ) {
                $cmv = $founded;
            } else {
                //append uri parts as variables for method invocation
                $cmv['vars'] = explode('/', $path);
            }
        }

        //finally we have something to show
        $this->loadController($cmv);
    }

    /**
     * Load controller
     * 
     * @param array $cmv array('controller' => '', 'method' => '', 'vars' => array())
     */
    private function loadController($cmv)
    {
        //if we have nothing to do, then quit
        if ( empty($cmv) OR empty($cmv['controller']) OR empty($cmv['method']) ) {
            trigger_error('Not call controller->method', E_USER_ERROR);
            exit;
        }

        if ( !is_array($cmv['controller']) ) {
            $cmv['controller'] = array($cmv['controller']);
        }

        self::$controller = implode("\\", $cmv['controller']);
        self::$method = $cmv['method'];

        //add controllers folder to begin and uppercase first letter class name
        array_unshift($cmv['controller'], 'controllers');
        end($cmv['controller']);
        $cmv['controller'][key($cmv['controller'])] = ucfirst($cmv['controller'][key($cmv['controller'])]);
        $cmv['controller'] = "\\" . implode("\\", $cmv['controller']);
        $controller = new $cmv['controller']($this->config, $this->router);

        if ( method_exists($controller, 'beforeMethod') ) {
            $controller->beforeMethod();
        }

        if ( is_callable(array($controller, $cmv['method']), true) ) {
            call_user_func_array(array($controller, $cmv['method']), $cmv['vars']);
        }

        if ( method_exists($controller, 'afterMethod') ) {
            $controller->afterMethod();
        }
    }

}

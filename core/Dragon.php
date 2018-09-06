<?php

namespace core;

use \Exception,
    core\debug\Generator AS DebugGenerator;

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
     * View
     *
     * @var View
     */
    private $view;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->router = new Router($this->config);
        $this->view = new View($this->config);

        //we need database config
        DB::$host = $this->config->get('dbServer');
        DB::$port = $this->config->get('dbPort');
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
        $debug = false;
        if (isset($GLOBALS['_GET']['debug'])) {
            $debug = $GLOBALS['_GET']['debug'] == 1;
        } elseif ($this->config->get('debug') !== null) {
            $debug = $this->config->get('debug') == 1;
        } else {
            $debug = IS_WORKSPACE;
        }
        define('DRAGON_DEBUG', $debug);
        
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
        
        //must be defined before view->render, sorry for hardcode
        if ( DRAGON_DEBUG ) {
            header('X-Dragon-Debug: ' . Dragon::$host . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);
        $this->view->render();
        
        DebugGenerator::generate();
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
        $this->view->setView(self::$controller . DS . self::$method);

        //add controllers folder to begin and uppercase first letter class name
        array_unshift($cmv['controller'], 'controllers');
        end($cmv['controller']);
        $cmv['controller'][key($cmv['controller'])] = ucfirst($cmv['controller'][key($cmv['controller'])]);
        $cmv['controller'] = "\\" . implode("\\", $cmv['controller']);
        $controller = new $cmv['controller']($this->config, $this->router, $this->view);

        if ( method_exists($controller, 'beforeMethod') ) {
            Debug::timer('beforeMethod');
            $controller->beforeMethod();
            Debug::timer('beforeMethod');
        }

        if ( is_callable(array($controller, $cmv['method']), true) ) {
            Debug::timer('Controller logic');
            call_user_func_array(array($controller, $cmv['method']), $cmv['vars']);
            Debug::timer('Controller logic');
        }

        if ( method_exists($controller, 'afterMethod') ) {
            Debug::timer('afterMethod');
            $controller->afterMethod();
            Debug::timer('afterMethod');
        }
    }

}

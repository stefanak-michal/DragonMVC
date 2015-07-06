<?php

namespace core;

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
    
        //we need database config ;)
        \DB::$host = $this->config->get('dbServer');
        \DB::$user = $this->config->get('dbUser');
        \DB::$password = $this->config->get('dbPass');
        \DB::$dbName = $this->config->get('dbDatabase');
        
        if ( !IS_WORKSPACE ) {
            \DB::$error_handler = function($params) {
                trigger_error(implode(PHP_EOL, $params), E_USER_WARNING);
            };
            
            \DB::$nonsql_error_handler = function($params) {
                trigger_error(implode(PHP_EOL, $params), E_USER_WARNING);
                header("HTTP/1.1 500 Internal Server Error");
                readfile(BASE_PATH . DS . '500.html');
                exit;
            };
        }
        
        self::$host = $this->config->get('project_host');
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
                $cmv['vars'] = explode('/', $path);
            }
        }

        //finally we have something to show
        $this->loadController($cmv);
    }
    
    /**
     * Show_error
     * 
     * @param int $code
     * @param string $message
     */
    public static function show_error($code = 404, $message = '')
    {
        $eMessage = $code . ' Error - ';
        
        switch ($code)
        {
            case 400:
                $eMessage .= 'Bad request';
                if ( ! empty($message))
                {
                    $eMessage .= ': ' . $message;
                }
                break;
            case 404:
                $eMessage .= 'File "' . $message . '" not found.';
                break;
        }
        
        exit($eMessage);
    }

    /**
     * Load controller
     * 
     * @param array $cmv array('controller' => '', 'method' => '', 'vars' => array())
     */
    public function loadController($cmv)
    {
        //if we have nothing to do, then quit
        if ( empty($cmv) OR  empty($cmv['controller']) OR empty($cmv['method']) )
        {
            self::show_error(400, 'Not call controller->method');
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

        if (method_exists($controller, 'beforeMethod'))
        {
            $controller->beforeMethod();
        }

        if ( is_callable(array($controller, $cmv['method']), true) )
        {
            call_user_func_array(array($controller, $cmv['method']), $cmv['vars']);
        }

        if (method_exists($controller, 'afterMethod'))
        {
            $controller->afterMethod();
        }
    }
    
}

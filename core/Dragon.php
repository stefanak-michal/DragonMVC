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
    public function __construct() { }
    
    /**
     * Run a project
     */
    public function run()
    {
        //default
        $this->config = new Config();
        $this->router = new Router($this->config);
        
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
        
        self::$controller = $cmv['controller'];
        self::$method = $cmv['method'];
        
        //add prefix
        $cmv['controller'] = '\\controllers\\' . ucfirst($cmv['controller']);
        $controller = new $cmv['controller']($this->config, $this->router);

        if (method_exists($controller, 'beforeFilter'))
        {
            $controller->beforeFilter();
        }

        if ( is_callable(array($controller, $cmv['method']), true) )
        {
            call_user_func_array(array($controller, $cmv['method']), $cmv['vars']);
        }

        if (method_exists($controller, 'afterFilter'))
        {
            $controller->afterFilter();
        }
    }
    
    /**
     * Autoload method
     * 
     * @param string $name
     */
    public function autoload($name)
    {
        $parts = explode("\\", $name);
        $parts = array_filter($parts);

        if ( count($parts) >= 2 )
        {
            $path = BASE_PATH;
            foreach ( $parts AS $i => $part )
            {
                if ( $i == 0 )
                {
                    $path .= DS . $part;
                }
                elseif ( $i == count($parts) - 1 )
                {
                    $path .= DS . ucfirst($part) . '.php';
                }
                else
                {
                    $path .= DS . $part;
                }
            }

            if ( file_exists($path) )
            {
                include_once($path);
            }
        }
    }
    
}

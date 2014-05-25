<?php

namespace core;

/**
 * Framework
 * 
 * Base class of MVC framework
 */
final class Framework
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
     * Construct
     */
    public function __construct()
    {
        //read all core files
        $coreFiles = glob(__DIR__ . DS . '*.php');
        foreach ($coreFiles AS $file)
        {
            include_once $file;
        }
    }
    
    /**
     * Run a project
     */
    public function run()
    {
        //default
        $config = new Config();
        $defaultCMV = array(
            'controller' => $config->get('defaultController'),
            'method' => $config->get('defaultMethod'),
            'vars' => array()
        );
        
        $cmv = $defaultCMV;
        
        // explode URI
        if (isset($_SERVER['PATH_INFO']))
        {
            $uri = $_SERVER['PATH_INFO'];
            $uri = preg_split("[\\/]", $uri, -1, PREG_SPLIT_NO_EMPTY);

            // we have some URI
            if (count($uri) > 0)
            {
                // find a write controller
                $cmv['controller'] = $uri[0];
                unset($uri[0]);

                // if we have something else, it is method
                if (isset($uri[1]))
                {
                    $cmv['method'] = $uri[1];
                    unset($uri[1]);
                }

                // if we still have something else, it is variables
                if ( ! empty($uri))
                {
                    foreach ($uri AS $value)
                    {
                        $cmv['vars'][] = $value;
                    }
                }
            }
        }

        unset($uri);
        
        //check route
        $router = new Router($config);
        if ( ! $router->existsRoute($cmv))
        {
            $cmv = $defaultCMV;
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
        $cmv['controller'] = '\\controller\\' . ucfirst($cmv['controller']);
        $controller = new $cmv['controller'];

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
                    $path .= DS . $part . 's';
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
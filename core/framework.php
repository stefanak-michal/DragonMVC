<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * Framework
 * 
 * Base class of MVC framework
 */
final class Framework
{
    /**
     * Instance of class
     *
     * @static
     * @var Framework
     */
    protected static $instance;
    
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
        
        //read all database model files
        $modelFiles = glob(BASE_PATH . DS . 'models' . DS . '*.php');
        if ( ! empty($modelFiles))
        {
            foreach ($modelFiles AS $file)
            {
                include_once $file;
            }
        }

        define('IS_WORKSPACE', (strpos(Config::gi()->get('project_host'), 'localhost') !== false) ? true : false);
        
        //we need database config ;)
        DB::$host = Config::gi()->get('dbServer');
        DB::$user = Config::gi()->get('dbUser');
        DB::$password = Config::gi()->get('dbPass');
        DB::$dbName = Config::gi()->get('dbDatabase');
//        DB::debugMode();
    }
    
    /**
     * Get instance of class
     * 
     * @return Framework
     * @static
     */
    public static function gi() 
    {
        if( self::$instance === NULL )
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Run a project
     */
    public function run()
    {
        //default
        $defaultCMV = array(
            'controller' => Config::gi()->get('defaultController'),
            'method' => Config::gi()->get('defaultMethod'),
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
        if ( ! Router::gi()->existsRoute($cmv))
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
    public function show_error($code = 404, $message = '')
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
     * Load model
     * 
     * @param string $model
     * @return object
     */
    public function loadModel($model)
    {
        if ( ! class_exists('Model'))
        {
            $this->show_error(400, 'Abstract Model not exists');
        }
        
        //add prefix
        $model = 'model' . ucfirst($model);
        if ( ! class_exists($model))
        {
            $this->show_error(400, 'Database Model not exists');
        }
        
        if ( ! isset($this->$model) OR ! $this->$model instanceof $model)
        {
            $this->$model = new $model();
        }
        
        return $this->$model;
    }

    /**
     * Load controller
     * 
     * @param array $cmv array('controller' => '', 'method' => '', 'vars' => array())
     */
    public function loadController($cmv)
    {
        if ( ! class_exists('Controller'))
        {
            $abstractControllerFile = BASE_PATH . DS . 'controllers' . DS . 'controller.php';
            if ( ! file_exists($abstractControllerFile))
            {
                $this->show_error(404, $abstractControllerFile);
            }
            include_once $abstractControllerFile;
        }
        
        //if we have nothing to do, then quit
        if ( empty($cmv) OR  empty($cmv['controller']) OR empty($cmv['method']) )
        {
            $this->show_error(400, 'Not call controller->method');
        }
        
        self::$controller = $cmv['controller'];
        self::$method = $cmv['method'];
        
        //add prefix
        $cmv['controller'] = 'controller' . ucfirst($cmv['controller']);
        $controller_file = BASE_PATH . DS . 'controllers' . DS . $cmv['controller'] . '.php';

        if ( ! file_exists($controller_file))
        {
            $this->show_error(404, $controller_file);
        }
        else
        {
            include_once $controller_file;
            unset($controller_file);
            
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
    }
    
    /**
     * Load component
     * 
     * @param string $component
     * @param array $arguments
     * @return object
     */
    public function loadComponent($component, $arguments = array())
    {
        //add prefix
        $component = 'component' . ucfirst($component);
        
        if ( ! isset($this->$component) OR empty($this->$component))
        {
            $component_file = BASE_PATH . DS . 'components' . DS . $component . '.php';

            if ( ! file_exists($component_file))
            {
                $this->show_error(404, $component_file);
            }
            else
            {
                include_once $component_file;
                unset($component_file);

                $reflection = new ReflectionClass($component);
                $this->$component = $reflection->newInstanceArgs((array) $arguments);
            }
        }
        
        return $this->$component;
    }
    
}
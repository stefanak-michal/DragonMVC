<?php

namespace core;

/**
 * Framework
 * Base class of MVC framework
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Dragon
{

    /**
     * Called controller
     * 
     * @static
     * @var \controllers\App
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
     * @static
     * @var array
     */
    public static $vars;

    /**
     * Run a project
     */
    public function run()
    {
        $cmv = array(
            'controller' => Config::gi()->get('defaultController'),
            'method' => Config::gi()->get('defaultMethod'),
            'vars' => array()
        );
        
        if (is_string($cmv['controller']))
            $cmv['controller'] = preg_split("/[\\/]/", $cmv['controller'], -1, PREG_SPLIT_NO_EMPTY);

        $_uri = new URI();
        $_uri->_fetch_uri_string();
        $path = (string) $_uri;

        $this->resolveRoute($cmv, $path);
        
        //must be defined before view->render, sorry for hardcode
        if ( DRAGON_DEBUG ) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);
        
        \core\debug\Generator::generate();
    }
    
    /**
     * @param array $cmv
     * @param string $path
     * @return void
     */
    private function resolveRoute(array &$cmv, string $path)
    {
        if ( empty($path) )
            return;
            
        $founded = Router::gi()->findRoute($path);
        if ( !empty($founded) ) {
            $cmv = $founded;
        } else {
            //append uri parts as variables for method invocation
            $cmv['vars'] = explode('/', $path);
        }
    }
    
    /**
     * @param array $cmv [controller, method, vars]
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

        //set view by CM
        View::gi()->view(implode("\\", $cmv['controller']) . DS . $cmv['method']);
        
        $class = $this->buildControllerName($cmv['controller']);
        self::$method = $cmv['method'];
        self::$vars = $cmv['vars'];
        self::$controller = new $class();

        if ( method_exists(self::$controller, 'beforeMethod') ) {
            Debug::timer('beforeMethod');
            self::$controller->beforeMethod();
            Debug::timer('beforeMethod');
        }

        if ( method_exists(self::$controller, self::$method) ) {
            Debug::timer('Controller logic');
            call_user_func_array(array(self::$controller, self::$method), self::$vars);
            Debug::timer('Controller logic');
        }

        if ( method_exists(self::$controller, 'afterMethod') ) {
            Debug::timer('afterMethod');
            self::$controller->afterMethod();
            Debug::timer('afterMethod');
        }
    }

    /**
     * @param array $c
     * @return string
     */
    private function buildControllerName(array $c): string
    {
        $last = ucfirst(array_pop($c));
        $name = '';
        if (count($c) > 0)
            $name = implode("\\", $c) . "\\";
        $name .= $last;

        return "\\" . 'controllers' . "\\" . $name;
    }

}

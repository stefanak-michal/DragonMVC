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
     * Run a project
     */
    public function run()
    {
        $cmv = array(
            'controller' => Config::gi()->get('defaultController'),
            'method' => Config::gi()->get('defaultMethod'),
            'vars' => array()
        );

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
        echo View::gi()->render();
        
        \core\debug\Generator::generate();
    }
    
    /**
     * @param array $cmv
     * @param string $path
     * @return
     */
    private function resolveRoute(&$cmv, $path)
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
        $controller = new $class();
        self::$method = $cmv['method'];

        if ( method_exists($controller, 'beforeMethod') ) {
            Debug::timer('beforeMethod');
            $controller->beforeMethod();
            Debug::timer('beforeMethod');
        }

        if ( method_exists($controller, self::$method) ) {
            Debug::timer('Controller logic');
            call_user_func_array(array($controller, self::$method), $cmv['vars']);
            Debug::timer('Controller logic');
        }

        if ( method_exists($controller, 'afterMethod') ) {
            Debug::timer('afterMethod');
            $controller->afterMethod();
            Debug::timer('afterMethod');
        }
    }
    
    /**
     * @param array $c
     * @return string
     */
    private function buildControllerName(array $c)
    {
        $last = ucfirst(array_pop($c));
        if (count($c) > 0)
            self::$controller = implode("\\", $c) . "\\";
        self::$controller .= $last;
        
        return "\\" . 'controllers' . "\\" . self::$controller;
    }

}

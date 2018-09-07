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

        if ( !empty($path) ) {
            $founded = Router::gi()->findRoute($path);
            if ( !empty($founded) ) {
                $cmv = $founded;
            } else {
                //append uri parts as variables for method invocation
                $cmv['vars'] = explode('/', $path);
            }
        }
        
        //must be defined before view->render, sorry for hardcode
        if ( DRAGON_DEBUG ) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);
        View::gi()->render();
        
        \core\debug\Generator::generate();
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
        View::gi()->setView(self::$controller . DS . self::$method);

        //add controllers folder to begin and uppercase first letter class name
        array_unshift($cmv['controller'], 'controllers');
        end($cmv['controller']);
        $cmv['controller'][key($cmv['controller'])] = ucfirst($cmv['controller'][key($cmv['controller'])]);
        $cmv['controller'] = "\\" . implode("\\", $cmv['controller']);
        $controller = new $cmv['controller']();

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

<?php

namespace controllers;

use core\Config,
    core\View,
    core\Router,
    \DB,
    helpers\Assets;

/**
 * controllerApp
 * 
 * Base controller for extending
 */
abstract class App
{
    /**
     * Instance for drawing views
     *
     * @var View
     */
    protected $view;
        
    /**
     * Instance for work with URI
     *
     * @var Router
     */
    protected $router;
    
    /**
     * Instance for configuration data
     *
     * @var Config
     */
    protected $config;
    
    /**
     * helper Assets
     *
     * @var Assets
     */
    protected $assets;
    
    /**
     * Construct
     */
    public function __construct($config, $router)
    {
        $this->config = $config;
        $this->router = $router;
        
        //we need database config ;)
        DB::$host = $this->config->get('dbServer');
        DB::$user = $this->config->get('dbUser');
        DB::$password = $this->config->get('dbPass');
        DB::$dbName = $this->config->get('dbDatabase');
        
        if ( !IS_WORKSPACE ) {
            DB::$error_handler = function($params) {
                trigger_error(implode(PHP_EOL, $params), E_USER_WARNING);
            };
        }
        
        $this->view = new View($this->config);
        $this->assets = new Assets($this->router);
    }
    
    /**
     * Alias for set variable to view
     * 
     * @param string $name
     * @param mixed $value
     */
    protected function set($name, $value)
    {
        $this->view->set($name, $value);
    }
    
    /**
     * Some action before method, what is needed ..or do not
     */
    public function beforeMethod()
    {
        $this->view->setTitle();
        $this->set('project_host', $this->config->get('project_host'));
        $this->set('project_title', $this->config->get('project_title'));
    }
    
    /**
     * Action after method
     */
    public function afterMethod()
    {
        $this->view->render();
    }
    
    /**
     * Get some global variable
     * 
     * @param string $name
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    final protected function param($name, $type = 'GET', $default = null)
    {
        $type = '_' . strtoupper($type);
        $output = isset($GLOBALS[$type][$name]) ? $GLOBALS[$type][$name] : $default;
        
        return $output;
    }
    
}

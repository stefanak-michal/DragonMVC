<?php

namespace controllers;

use core\Config,
    core\View,
    core\Router,
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
     * Construct
     */
    public function __construct($config, $router)
    {
        $this->config = $config;
        $this->router = $router;
        
        $this->view = new View($this->config);
        Assets::setRouter($this->router);
    }
    
    /**
     * Alias for set variable to view
     * 
     * @param string $name
     * @param mixed $value
     */
    final protected function set($name, $value)
    {
        $this->view->set($name, $value);
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
    
}

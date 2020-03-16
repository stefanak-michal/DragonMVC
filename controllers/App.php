<?php

namespace controllers;

/**
 * controllerApp
 * 
 * Base controller for extending
 */
abstract class App
{
    /**
     * Config - for simple access
     * 
     * @var \core\Config
     */
    protected $config;
    /**
     * View - for simple access
     * 
     * @var \core\View
     */
    protected $view;
    /**
     * Router - for simple access
     * 
     * @var \core\Router
     */
    protected $router;
    
    public function __construct()
    {
        $this->config = \core\Config::gi();
        $this->view = \core\View::gi();
        $this->router = \core\Router::gi();
    }

    
    /**
     * Alias for set variable to View
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
        return \helpers\Utils::param($name, $type, $default);
    }
    
    /**
     * Some action before method, what is needed ..or do not
     */
    public function beforeMethod()
    {
        $this->set('project_title', $this->config->get('project_title'));
    }
    
    /**
     * Action after method
     */
    public function afterMethod()
    {
        echo $this->view->render();
    }
    
}

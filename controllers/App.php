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
     * Alias for set variable to View
     * 
     * @param string $name
     * @param mixed $value
     */
    final protected function set($name, $value)
    {
        \core\View::gi()->set($name, $value);
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
        
        $type = '_' . strtoupper($type);
        $output = isset($GLOBALS[$type][$name]) ? $GLOBALS[$type][$name] : $default;
        
        return $output;
    }
    
    /**
     * Some action before method, what is needed ..or do not
     */
    public function beforeMethod()
    {
        \core\View::gi()->setTitle();
        $this->set('project_host', \core\Config::gi()->get('project_host'));
        $this->set('project_title', \core\Config::gi()->get('project_title'));
    }
    
    /**
     * Action after method
     */
    public function afterMethod()
    {
        
    }
    
}

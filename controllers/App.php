<?php

namespace controller;

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
     * @var core\View
     */
    protected $view;
        
    /**
     * Instance for work with URI
     *
     * @var core\Router
     */
    protected $router;
    
    /**
     * Instance for configuration data
     *
     * @var core\Config
     */
    protected $config;
    
    /**
     * Construct
     */
    public function __construct()
    {
        $this->config = new \core\Config();
        //we need database config ;)
        \DB::$host = $this->config->get('dbServer');
        \DB::$user = $this->config->get('dbUser');
        \DB::$password = $this->config->get('dbPass');
        \DB::$dbName = $this->config->get('dbDatabase');
        
        $this->view = new \core\View();
        $this->router = new \core\Router($this->config);
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
    protected function beforeFilter()
    {
        //base actions
        $this->view->setTitle();
        $this->set('project_host', $this->config->get('project_host'));
        $this->set('project_title', $this->config->get('project_title'));

        //add to view all needed CSS
        $cssFiles = $this->config->get('css');
        $tplCssFiles = array();
        if ( ! empty($cssFiles))
        {
            foreach ($cssFiles AS $name => $cssFile)
            {
                $tplCssFiles[] = $this->router->getAssetUrl($name, 'css');
            }
        }
        $this->set('cssFiles', $tplCssFiles);
        unset($cssFiles, $tplCssFiles);
        
        //add to view all needed JS
        $jsFiles = $this->config->get('js');
        $tplJsFiles = array();
        if ( ! empty($jsFiles))
        {
            foreach ($jsFiles AS $name => $jsFile)
            {
                $tplJsFiles[] = $this->router->getAssetUrl($name, 'js');
            }
        }
        $this->set('jsFiles', $tplJsFiles);
        unset($jsFiles, $tplJsFiles);
    }
    
    /**
     * Action after method
     */
    protected function afterFilter()
    {
        $this->view->render();
    }
    
    /**
     * Get some global variable
     * 
     * @param string $name
     * @param int $type
     * @param mixed $default
     * @return mixed
     */
    protected function param($name, $type = INPUT_GET, $default = null)
    {
        $output = filter_input($type, $name);
        return $output ?: $default;
    }
    
}
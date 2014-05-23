<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * controllerApp
 * 
 * Base controller for extending
 */
abstract class Controller
{
    /**
     * Instance for drawing views
     *
     * @access protected
     * @var View
     */
    protected $view;
        
    /**
     * Instance for work with URI
     *
     * @access protected
     * @var Router
     */
    protected $router;
    
    /**
     * Access to database
     *
     * @access protected
     * @var DB
     */
    protected $database;
    
    /**
     * Construct
     */
    public function __construct()
    {
        $this->view = new View();
        $this->router = new Router();
        
        $this->database = DB::getMDB();
        
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
        $this->set('project_host', Config::gi()->get('project_host'));
        $this->set('project_title', Config::gi()->get('project_title'));
        
        //add to view all needed CSS
        $cssFiles = Config::gi()->get('css');
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
        $jsFiles = Config::gi()->get('js');
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
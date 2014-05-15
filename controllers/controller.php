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
     * Allowed global variable types
     *
     * @access private
     * @var array 
     */
    private $allowGlobal = array('get', 'post', 'files', 'session', 'cookie');
    
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
     * Some action before method, what is needed ..or do not
     */
    protected function beforeFilter()
    {
        //base actions
        $this->view->setTitle();
        $this->view->set('project_host', Config::gi()->get('project_host'));
        $this->view->set('project_title', Config::gi()->get('project_title'));
        
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
        $this->view->set('cssFiles', $tplCssFiles);
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
        $this->view->set('jsFiles', $tplJsFiles);
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
     * @param string $type Allowed, look $this->allowGlobal
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function param($type, $name, $default = null)
    {
        $output = $default;
        
        if ( ! empty($type) AND ! empty($name) AND in_array(strtolower($type), $this->allowGlobal))
        {
            switch (strtolower($type))
            {
                case 'get':
                    if (isset($_GET[$name]))
                    {
                        $output = $_GET[$name];
                    }
                    break;
                
                case 'post':
                    if (isset($_POST[$name]))
                    {
                        $output = $_POST[$name];
                    }
                    break;
                    
                case 'files':
                    if (isset($_FILES[$name]))
                    {
                        $output = $_FILES[$name];
                    }
                    break;
                    
                case 'session':
                    if (isset($_SESSION[$name]))
                    {
                        $output = $_SESSION[$name];
                    }
                    break;
                    
                case 'cookie':
                    if (isset($_COOKIE[$name]))
                    {
                        $output = $_COOKIE[$name];
                    }
                    break;
            }
        }
        
        return $output;
    }
    
}
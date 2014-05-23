<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * controllerHomepage
 */
class controllerHomepage extends Controller
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        
    }
    
    /**
     * Some action before any method
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        
        //layout stranky
        $this->view->setLayout('clear');
    }
    
    /**
     * Some action after any method
     */
    public function afterFilter()
    {
        parent::afterFilter();
    }
    
    /**
     * Homepage -- default method
     */
    public function index()
    {
        
        /*
         * Sample information about access to database data
         * Default read:
         * $this->database->sample->get();
         * Explanation of command:
         * $this->(class variable)->(model name)->(method from model);
         */
         
        /*
         * Sample get instance of component
         * $componentEmail = Framework::gi()->loadComponent('email', array($this->router));
         */
        
        $this->set('variable', 'how to set variable to view');
    }
    
    
}
<?php

namespace controller;

/**
 * controllerHomepage
 */
class Homepage extends App
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
         * Explanation of command:
         * $this->(class variable)->(model name)->(method from model);
         * Default read:
         */
        $modelSample = new \model\Sample();
//        $modelSample->get();
         
        /*
         * Sample get instance of component
         */
        $componentEmail = new \component\Email($this->config, $this->router);
        
        $this->set('variable', 'how to set variable to view');
    }
    
    
}
<?php

namespace controllers;

/**
 * Api calls
 */
class Api extends App
{
    
    public function __construct($config, $router)
    {
        if ( !IS_WORKSPACE && !isset($_SERVER["HTTP_X_REQUESTED_WITH"]) ) {
            exit;
        }
        
        parent::__construct($config, $router);
    }
    
    public function beforeMethod()
    {
        parent::beforeMethod();
        
        $this->view->setLayout(false);
        $this->view->setView('api/clear');
        $this->set('data', '');
    }
    
    public function confirm()
    {
        //any api method for xhr (ajax)
    }
    
}

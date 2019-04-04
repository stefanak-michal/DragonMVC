<?php

namespace controllers;

/**
 * Api calls
 */
class Api extends App
{
    
    public function __construct()
    {
        if ( !IS_WORKSPACE && $this->param('HTTP_X_REQUESTED_WITH', 'SERVER') == null ) {
            exit;
        }
        
        parent::__construct();
    }
    
    public function beforeMethod()
    {
        parent::beforeMethod();
        
        $this->view->layout(false);
        $this->view->view('api/clear');
        $this->set('data', '');
    }
    
    public function confirm()
    {
        //any api method for xhr (ajax)
    }
    
}

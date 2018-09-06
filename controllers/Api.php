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
        
        \core\View::gi()->setLayout(false);
        \core\View::gi()->setView('api/clear');
        $this->set('data', '');
    }
    
    public function confirm()
    {
        //any api method for xhr (ajax)
    }
    
}

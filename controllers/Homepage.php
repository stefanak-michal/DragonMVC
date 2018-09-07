<?php

namespace controllers;

use helpers\Assets,
    models\Sample AS mSample,
    components\Email AS cEmail,
    core\Debug;

/**
 * controllerHomepage
 */
class Homepage extends App
{
    
    public function beforeMethod()
    {
        parent::beforeMethod();
        
        //page layout
        $this->view->setLayout('default');
    }
    
    /**
     * Some action after any method
     */
    public function afterMethod()
    {
        parent::afterMethod();
    }
    
    /**
     * Homepage -- default method
     */
    public function index()
    {
        Debug::timer('test');
        Assets::add('main', Assets::TYPE_CSS);
        Assets::add('default', Assets::TYPE_JS);
        
        /*
         * Sample information about access to database data
         * Default read:
         */
//        $modelSample = new mSample();
//        $rows = $modelSample->get();
//        Debug::var_dump($rows);
//        $modelSample->get(2);
        
        /*
         * Sample get instance of component
         */
        $componentEmail = new cEmail();
        
        Debug::var_dump('test');
        Debug::var_dump([432, 654]);
        Debug::timer('test');
        
        $this->set('variable', 'how to set variable to view');
        $this->set('links', array(
            'produkt' => $this->router->getUrl('products', 'detail', 123, array('list' => 5))
        ));
    }
    
}

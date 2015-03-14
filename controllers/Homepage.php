<?php

namespace controllers;

use helpers\Assets,
    models\Sample AS mSample,
    components\Email AS cEmail;

/**
 * controllerHomepage
 */
class Homepage extends App
{
    
    /**
     * Some action before any method
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        
        //layout stranky
        $this->view->setLayout('default');
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
        $this->assets
                ->add('main', Assets::TYPE_CSS)
                ->add('default', Assets::TYPE_JS);
        
        /*
         * Sample information about access to database data
         * Explanation of command:
         * $this->(class variable)->(model name)->(method from model);
         * Default read:
         */
        $modelSample = new mSample();
//        $modelSample->get();
         
        /*
         * Sample get instance of component
         */
        $componentEmail = new cEmail($this->config, $this->router);
        
        $this->set('variable', 'how to set variable to view');
        $this->set('links', array(
            'produkt' => $this->router->getUrl('products', 'detail', 123, array('list' => 5))
        ));
    }
    
}

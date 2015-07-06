<?php

namespace controllers;

use components\Auth,
    models\Users AS mUsers,
    core\Dragon;

/**
 * Administration
 */
class Admin extends App
{
    /**
     * Auth component
     *
     * @var Auth
     */
    private $auth;
    /**
     * Model Users
     *
     * @var Users
     */
    private $mUsers;
    
    public function beforeMethod()
    {
        $this->mUsers = new mUsers();
        $this->auth = new Auth($this->mUsers, $this->config);
        
        if ( Dragon::$method != 'login' && !$this->auth->check() ) {
            $this->router->redirect( $this->config->get('project_host') );
        }
        
        parent::beforeMethod();
        
        $this->view->setLayout('default');
        $this->assets
                ->add('main', Assets::TYPE_CSS)
                ->add('default', Assets::TYPE_JS);
    }
    
    /**
     * Login admin
     */
    public function login()
    {
        //verify form login
        $data = $this->param('data', 'post');
        if ( !empty($data) && $this->auth->login($data['nick'], $data['pass'], true) ) {
            $this->router->redirect( $this->router->getUrl('admin', 'confirm') );
        }
        
        $this->set('formUrl', $this->router->current());
    }
    
    /**
     * Confirm queue
     */
    public function confirm()
    {
        //any subpage
    }
    
}

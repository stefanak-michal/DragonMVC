<?php

namespace controllers;

use components\Auth,
    models\Users AS mUsers,
    helpers\Assets;

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
     * @var mUsers
     */
    private $mUsers;
    
    public function beforeMethod()
    {
        $this->mUsers = new mUsers();
        $this->auth = new Auth($this->mUsers);
        
        if ( \core\Dragon::$method != 'login' && !$this->auth->check() ) {
            $this->router->redirect( $this->router->homepage() );
        }
        
        parent::beforeMethod();
        
        $this->view->setLayout('default');
        Assets::add('main', Assets::TYPE_CSS);
        Assets::add('default', Assets::TYPE_JS);
    }
    
    /**
     * Login admin
     */
    public function login()
    {
        //verify form login
        $data = $this->param('data', 'post');
        if ( !empty($data) && $this->auth->login($data['nick'], $data['pass'], true) ) {
            $this->router->redirect( $this->router->url(self::class, 'confirm') );
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

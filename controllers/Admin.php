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
     * @var Users
     */
    private $mUsers;
    
    public function beforeMethod()
    {
        $this->mUsers = new mUsers();
        $this->auth = new Auth($this->mUsers, $this->config);
        
        if ( \core\Dragon::$method != 'login' && !$this->auth->check() ) {
            \core\Router::gi()->redirect( \core\Config::gi()->get('project_host') );
        }
        
        parent::beforeMethod();
        
        \core\View::gi()->setLayout('default');
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
            \core\Router::gi()->redirect( \core\Router::gi()->getUrl('admin', 'confirm') );
        }
        
        $this->set('formUrl', \core\Router::gi()->current());
    }
    
    /**
     * Confirm queue
     */
    public function confirm()
    {
        //any subpage
    }
    
}

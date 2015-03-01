<?php

namespace components;

use models\Users;

/**
 * Auth
 * 
 * Things about session
 */
class Auth
{
    /**
     * model Users
     *
     * @var Users
     */
    private $mUsers;
    
    /**
     * Core config
     *
     * @var Config
     */
    private $config;
    
    /**
     * Session key
     *
     * @var type 
     */
    const SESSION_KEY = 'instance';
    
    /**
     * Construct
     */
    public function __construct($mUsers, $config)
    {
        session_start();
        
        $this->mUsers = $mUsers;
        $this->config = $config;
    }
    
    /**
     * Login user
     * 
     * @param string $user
     * @param string $password
     * @param boolean $remember
     * @return boolean
     */
    public function login($user, $password, $remember)
    {
        $output = false;
        
        if ( ! empty($user) AND ! empty($password) AND preg_match('/[\w\d]+/', $password))
        {
            $password = $this->generatePasswordHash($password);
            
            $userData = $this->mUsers->getByNick($user);
            if ( empty($userData) ) {
                $userData = $this->mUsers->getByEmail($user);
            }
            
            if ( ! empty($userData) AND $userData['password'] == $password AND $userData['cisStatus'] == Users::STATUS_ACTIVE)
            {
                $_SESSION[ self::SESSION_KEY ] = array();
                session_regenerate_id();
                $_SESSION[ self::SESSION_KEY ] = array('idUser' => $userData['idUser'], 'nick' => $userData['nick'], 'time' => time());
                
                $sessionHash = '';
                if ($remember)
                {
                    $sessionHash = md5($this->config->get('salt') . $_SERVER['REMOTE_ADDR'] . session_id() . $userData['nick']);
                    setcookie('login', $sessionHash, strtotime('+1 month'), '/');
                }
                else
                {
                    setcookie('login', '', time() - 3600, '/');
                }
                
                $this->updateSession($_SERVER['REMOTE_ADDR'] . ' ' . $sessionHash);
                $output = true;
            }
        }
        
        return $output;
    }
    
    /**
     * Generate password hash
     * 
     * @param string $password
     * @return string
     */
    public function generatePasswordHash($password)
    {
        return md5( $this->config->get('salt') . $password);
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        if (isset($_SESSION[ self::SESSION_KEY ]))
        {
            $this->updateSession('');
            setcookie('login', '', time() - 3600, '/');
            unset($_SESSION[ self::SESSION_KEY ]);
        }

        if (isset($_SESSION) AND ! empty($_SESSION))
        {
            session_destroy();
        }
    }
    
    /**
     * Check if user is logged
     * 
     * @return boolean
     */
    public function check()
    {
        $output = false;
        
        if ( ! empty($_COOKIE['login']))
        {
            //v cookie je session hash, s dalsimi pridavkami to overime v DB
            $userData = $this->mUsers->getBySession($_SERVER['REMOTE_ADDR'] . ' ' . $_COOKIE['login']);
            if ( ! empty($userData))
            {
                $_SESSION[ self::SESSION_KEY ] = array('idUser' => $userData['idUser'], 'nick' => $userData['nick']);
                $output = true;
            }
        }
        elseif (isset($_SESSION[ self::SESSION_KEY ]['time']) AND $_SESSION[ self::SESSION_KEY ]['time'] >= time() - 3600)
        {
            $output = true;
        }

        if ($output)
        {
            $_SESSION[ self::SESSION_KEY ]['time'] = time();
            $this->updateSession();
        }
        else
        {
            $this->logout();
        }
        
        return $output;
    }
    
    /**
     * Return current idUser
     * 
     * @return int idUser
     */
    public function currentUser()
    {
        $output = 0;
        
        if (isset($_SESSION[ self::SESSION_KEY ]['idUser']))
        {
            $output = $_SESSION[ self::SESSION_KEY ]['idUser'];
        }
        
        return $output;
    }
    
    /**
     * Help method which update last activity time of user
     * 
     * @param string $sessionHash
     */
    private function updateSession($sessionHash = null)
    {
        $this->mUsers->updateSession($this->currentUser(), $sessionHash);
    }
    
}

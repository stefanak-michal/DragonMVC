<?php

namespace models;

use core\DB;

/**
 * Users
 * 

CREATE TABLE `users` (
	`idUser` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`nick` VARCHAR(50) NOT NULL,
	`password` VARCHAR(32) NOT NULL,
	`email` VARCHAR(100) NOT NULL,
	`lastSession` VARCHAR(48) NULL DEFAULT NULL,
	`lastActivityTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`cisStatus` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
	PRIMARY KEY (`idUser`),
	INDEX `nick` (`nick`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

 */
class Users extends Model
{
    /**
     * Active
     */
    const STATUS_ACTIVE = 0;
    /**
     * Waiting for assig. state
     */
    const STATUS_WAITING = 1;
    /**
     * Inactive
     */
    const STATUS_INACTIVE = 2;
    
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'users';
    /**
     * Primary key column
     *
     * @var string
     */
    protected $primary_key = 'idUser';
    
    /**
     * Update last activity time
     * 
     * @param int $idUser
     * @param string $sessionHash
     */
    public function updateSession($idUser, $sessionHash = null)
    {
        if ( ! empty($idUser))
        {
            $update = array('lastActivityTime' => DB::sqlEval('NOW()'));
            
            if ($sessionHash !== null)
            {
                $update['lastSession'] = $sessionHash;
            }
            
            $this->db->update($this->table, $update, $this->primary_key . ' = %i', $idUser);
        }
    }
    
    /**
     * Update user
     * 
     * @param int $idUser
     * @param array $columns
     */
    public function update($idUser, $columns)
    {
        if ( ! empty($idUser) AND is_array($columns) AND ! empty($columns))
        {
            $this->db->update($this->table, $columns, $this->primary_key . ' = %i', $idUser);
        }
    }
    
    /**
     * Find user by session
     * 
     * @param string $sessionHash
     * @return array
     */
    public function getBySession($sessionHash)
    {
        $output = array();
        
        if ( ! empty($sessionHash))
        {
            $output = $this->db->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE lastSession = %s', $sessionHash);
        }
        
        return $output;
    }
    
    /**
     * Find user by nick
     * 
     * @param string $nick
     * @return array
     */
    public function getByNick($nick)
    {
        $output = array();
        
        if ( ! empty($nick))
        {
            $output = $this->db->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE nick LIKE %s', $nick);
        }
        
        return $output;
    }
    
    /**
     * Find user by email
     * 
     * @param string $email
     * @return array
     */
    public function getByEmail($email)
    {
        $output = array();
        
        if ( ! empty($email))
        {
            $output = $this->db->queryFirstRow('SELECT * FROM ' . $this->table . ' WHERE email LIKE %s', $email);
        }
        
        return $output;
    }
    
    /**
     * Change status
     * 
     * @param int $idUser
     * @param int $status
     */
    public function changeStatus($idUser, $status = self::STATUS_ACTIVE)
    {
        if ( ! empty($idUser) AND is_int($status))
        {
            $this->db->update($this->table, array('cisStatus' => $status), 'idUser = %i', $idUser);
        }
    }
    
    /**
     * Clear old accounts
     * 
     * @param int $days
     */
    public function deleteOldUsers($days)
    {
        if ( ! empty($days))
        {
            $this->db->delete($this->table, 'lastActivityTime < DATE_SUB(NOW(), INTERVAL %i DAY)', $days);
        }
    }
    
}
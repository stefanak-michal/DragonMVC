<?php

namespace models;

use \DB,
    helpers\ArrayUtil;

/**
 * Model
 * 
 * Base database model for extending
 */
abstract class Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table;
    /**
     * Primary key column
     *
     * @var string
     */
    protected $primary_key;
    
    /**
     * MeekroDB
     *
     * @var MeekroDB
     */
    protected $db;
    
    /**
     * Construct
     */
    public function __construct()
    {
        $this->db = DB::getMDB();
    }
    
    /**
     * Base reading data from table
     * 
     * @param array $ids
     * @return array
     */
    public function get($ids = array())
    {
        $output = array();
        
        if (empty($ids))
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table);
        }
        elseif ( ! is_array($ids))
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', $ids);
        }
        elseif (count($ids) == 1)
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', reset($ids));
        }
        else
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
        }
        
        return ArrayUtil::reIndex($output, $this->primary_key);
    }
    
    /**
     * Delete row by primary key
     * 
     * @param int $id
     */
    public function delete($id)
    {
        if ( is_numeric($id) ) {
            $this->db->delete($this->table, $this->primary_key . ' = %i', $id);
        }
    }
    
    /**
     * Destruktor
     */
    public function __destruct()
    {
        $this->db->disconnect();
    }
    
}

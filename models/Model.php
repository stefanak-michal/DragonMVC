<?php

namespace models;

use core\DB,
    helpers\ArrayUtil;

/**
 * Model
 * 
 * Base database model with CRUD actions for extending
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
     * @var core\MeekroDB
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
     * Insert new row
     * 
     * @param array $data
     * @return int
     */
    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insertId();
    }
    
    /**
     * Alias for read
     * 
     * @param array|int $ids
     * @return array
     */
    public function get($ids = array())
    {
        return $this->read($ids);
    }
    
    /**
     * Base reading data from table
     * 
     * @param array|int $ids
     * @return array
     */
    public function read($ids = array())
    {
        $output = array();
        
        if (empty($ids))
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table);
        }
        elseif ( is_numeric($ids) || (is_array($ids) && count($ids) == 1) )
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', is_numeric($ids) ? $ids : reset($ids));
        }
        elseif ( is_array($ids) )
        {
            $output = $this->db->query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
        }
        
        return ArrayUtil::reIndex($output, $this->primary_key);
    }
    
    /**
     * Update row
     * 
     * @param int $id
     * @param array $data
     */
    public function update($id, $data)
    {
        $this->db->update($this->table, $data, $this->primary_key . ' = %i', $id);
        return $this->db->affectedRows();
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

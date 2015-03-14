<?php

namespace models;

use \DB,
    \DBHelper;

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
     * Construct
     */
    public function __construct()
    {
        
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
            $output = DB::query('SELECT * FROM ' . $this->table);
        }
        elseif ( ! is_array($ids))
        {
            $output = DB::query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', $ids);
        }
        elseif (count($ids) == 1)
        {
            $output = DB::query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = %i', reset($ids));
        }
        else
        {
            $output = DB::query('SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' IN %li', $ids);
        }
        
        return DBHelper::reIndex($output, $this->primary_key);
    }
    
    /**
     * Delete row by primary key
     * 
     * @param int $id
     */
    public function delete($id)
    {
        if ( is_numeric($id) ) {
            DB::delete($this->table, $this->primary_key . ' = %i', $id);
        }
    }
    
}

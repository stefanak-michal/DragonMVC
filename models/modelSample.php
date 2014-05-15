<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * modelSample
 * 
 * Sample model for table "Sample"
 */
class modelSample extends Model
{
    /**
     * Const for column status
     */
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'sample';
    /**
     * Primary key column
     *
     * @var string
     */
    protected $primary_key = 'idSample';
    
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
    }
    
}
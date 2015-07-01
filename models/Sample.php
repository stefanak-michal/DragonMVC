<?php

namespace models;

/**
 * modelSample
 * 
 * Sample model for table "Sample"
 */
class Sample extends Model
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
    
}

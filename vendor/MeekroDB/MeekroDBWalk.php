<?php

namespace MeekroDB;

use mysqli, mysqli_result;

/**
 * Class MeekroDBWalk
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @package MeekroDB
 * @see https://github.com/SergeyTsalkov/meekrodb
 */
class MeekroDBWalk
{
    /**
     * @var mysqli
     */
    protected $mysqli;
    /**
     * @var mixed
     */
    protected $result;

    /**
     * MeekroDBWalk constructor.
     * @param mysqli $mysqli
     * @param mixed $result
     */
    function __construct(MySQLi $mysqli, $result)
    {
        $this->mysqli = $mysqli;
        $this->result = $result;
    }

    function next()
    {
        // $result can be non-object if the query was not a SELECT
        if (!($this->result instanceof MySQLi_Result)) return;
        if ($row = $this->result->fetch_assoc()) return $row;
        else $this->free();
    }

    function free()
    {
        if (!($this->result instanceof MySQLi_Result)) return;

        $this->result->free();
        while ($this->mysqli->more_results()) {
            $this->mysqli->next_result();
            if ($result = $this->mysqli->use_result()) $result->free();
        }

        $this->result = null;
    }

    function __destruct()
    {
        $this->free();
    }
}

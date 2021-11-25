<?php

namespace MeekroDB;

/**
 * Class MeekroDBException
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @author Michal Stefanak
 * @package MeekroDB
 * @see https://github.com/SergeyTsalkov/meekrodb
 * @see https://github.com/stefanak-michal/meekrodb
 */
class MeekroDBException extends \Exception
{
    /**
     * @var string
     */
    protected $query = '';

    /**
     * MeekroDBException constructor.
     * @param string $message
     * @param string $query
     * @param int $code
     */
    function __construct(string $message = '', string $query = '', int $code = 0)
    {
        parent::__construct($message);
        $this->query = $query;
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}

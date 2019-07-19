<?php

namespace MeekroDB;

use Throwable;

class MeekroDBException implements Throwable
{
    /**
     * @var string
     */
    protected $query = '';
    /**
     * @var integer
     */
    protected $code = 0;
    /**
     * @var string
     */
    protected $message = '';
    /**
     * @var Throwable|null
     */
    protected $previous;

    /**
     * @param string $message
     * @param string $query
     * @param integer $code
     * @param Throwable|null $previous
     */
    function __construct($message = '', $query = '', $code = 0, $previous = null)
    {
        $this->message = $message;
        $this->query = $query;
        $this->code = $code;
        $this->previous = $previous;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getFile(): string
    {
        trigger_error('not defined');
        return '';
    }

    public function getLine(): int
    {
        trigger_error('not defined');
        return 0;
    }

    public function getTrace(): array
    {
        trigger_error('not defined');
        return [];
    }

    public function getTraceAsString(): string
    {
        trigger_error('not defined');
        return '';
    }

    public function getPrevious(): Throwable
    {
        trigger_error('not defined');
        return new \Exception;
    }

    public function __toString(): string
    {
        trigger_error('not defined');
        return '';
    }
}

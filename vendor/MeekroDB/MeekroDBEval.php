<?php

namespace MeekroDB;

/**
 * Class MeekroDBEval
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @package MeekroDB
 * @see https://github.com/SergeyTsalkov/meekrodb
 */
class MeekroDBEval
{
    public $text = '';

    function __construct($text)
    {
        $this->text = $text;
    }
}

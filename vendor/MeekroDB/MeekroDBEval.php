<?php

namespace MeekroDB;

/**
 * Class MeekroDBEval
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @author Michal Stefanak
 * @package MeekroDB
 * @see https://github.com/SergeyTsalkov/meekrodb
 * @see https://github.com/stefanak-michal/meekrodb
 */
class MeekroDBEval
{
    public $text = '';

    function __construct($text)
    {
        $this->text = $text;
    }
}

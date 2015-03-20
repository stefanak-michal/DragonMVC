<?php

namespace helpers;

/**
 * Pomocne validacne funkcie
 */
class Validation
{
    /**
     * Ci je to naozaj email
     * 
     * @param string $email
     * @return boolean
     */
    public static function isEmail($email)
    {
        return preg_match("/([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4})/", $email);
    }
    
}

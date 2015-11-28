<?php

namespace helpers;

/**
 * Validation helper functions
 */
class Validation
{
    /**
     * Check email
     * 
     * @param string $email
     * @return boolean
     */
    public static function isEmail($email)
    {
        return preg_match("/([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4})/", $email);
    }
    
    /**
     * Check url
     * 
     * @param string $url
     * @return boolean 
     */
    public static function isUrl($url)
    {
        return preg_match("/^(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/", $url);
    }
    
    /**
     * Check path
     * 
     * @param string $string
     * @return boolean
     */
    public static function isPath($string)
    {
        $output = false;
        
        $begin = substr($string, 0, 3);
        if ( strpos($begin, DS) !== false ) {
            $output = true;
        }
        
        return $output;
    }
    
}

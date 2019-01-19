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
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check url
     *
     * @param string $url
     * @return boolean
     */
    public static function isUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
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

    /**
     * Replace all empty strings with null
     * @param array $data
     */
    public static function nullify(array &$data)
    {
        foreach ($data as &$value) {
            if ($value === '')
                $value = null;
        }
    }
    
}

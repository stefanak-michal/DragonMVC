<?php

namespace helpers;

/**
 * Different utils helper functions
 */
class Utils
{

    /**
     * Closing tags in html
     * 
     * @param string $text
     * @return string
     */
    public static function closeTags($text)
    {
        $patt_open = "%((?<!</)(?<=<)[\s]*[^/!>\s]+(?=>|[\s]+[^>]*[^/]>)(?!/>))%";
        $patt_close = "%((?<=</)([^>]+)(?=>))%";
        if ( preg_match_all($patt_open, $text, $matches) ) {
            $m_open = $matches[1];
            if ( !empty($m_open) ) {
                preg_match_all($patt_close, $text, $matches2);
                $m_close = $matches2[1];
                if ( count($m_open) > count($m_close) ) {
                    $c_tags = array();
                    $m_open = array_reverse($m_open);
                    foreach ( $m_close as $tag ) {
                        if ( !empty($tag) ) {
                            if ( !isset($c_tags[$tag]) ) {
                                $c_tags[$tag] = 0;
                            }

                            $c_tags[$tag] ++;
                        }
                    }
                    foreach ( $m_open as $k => $tag ) {
                        if ( isset($c_tags[$tag]) AND $c_tags[$tag] -- <= 0 ) {
                            $text .= '</' . $tag . '>';
                        }
                    }
                }
            }
        }
        return $text;
    }

    /**
     * Remove diacritic
     * 
     * @param string $text
     * @param boolean $toLowerCase
     * @return string
     */
    public static function removeDiacritic($text, $toLowerCase = false)
    {
        // also for multi-byte (napr. UTF-8)
        $transform = array(
            'ö' => 'o',
            'ű' => 'u',
            'ő' => 'o',
            'ü' => 'u',
            'ł' => 'l',
            'ż' => 'z',
            'ń' => 'n',
            'ć' => 'c',
            'ę' => 'e',
            'ś' => 's',
            'ŕ' => 'r',
            'á' => 'a',
            'ä' => 'a',
            'ĺ' => 'l',
            'č' => 'c',
            'é' => 'e',
            'ě' => 'e',
            'í' => 'i',
            'ď' => 'd',
            'ň' => 'n',
            'ó' => 'o',
            'ô' => 'o',
            'ř' => 'r',
            'ů' => 'u',
            'ú' => 'u',
            'š' => 's',
            'ť' => 't',
            'ž' => 'z',
            'ľ' => 'l',
            'ý' => 'y',
            'Ŕ' => 'R',
            'Á' => 'A',
            'Ä' => 'A',
            'Ĺ' => 'L',
            'Č' => 'C',
            'É' => 'E',
            'Ě' => 'E',
            'Í' => 'I',
            'Ď' => 'D',
            'Ň' => 'N',
            'Ó' => 'O',
            'Ô' => 'O',
            'Ř' => 'R',
            'Ů' => 'U',
            'Ú' => 'U',
            'Š' => 'S',
            'Ť' => 'T',
            'Ž' => 'Z',
            'Ľ' => 'L',
            'Ý' => 'Y',
            'Ä' => 'A'
        );

        $text = strtr($text, $transform);
        return $toLowerCase ? strtolower($text) : $text;
    }

    /**
     * Return size in bytes
     * 
     * @param string $val
     * @return int
     */
    public static function decodeBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ( $last ) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
    
    /**
     * Return size in human readable version
     * 
     * @param int $size
     * @return string
     */
    public static function encodeBytes($size)
    {
        $size = (int) $size;
        $units = ['kB', 'MB', 'GB'];
        
        $outputUnit = 'b';
        $output = $size;
        
        while ( $output > 1024 ) {
            if ( empty($units) ) {
                break;
            }
            
            $output = $output / 1024;
            $outputUnit = array_shift($units);
        }
        
        return round($output, 2) . $outputUnit;
    }

    /**
     * Return client IP
     * 
     * @return string
     */
    public static function realIp()
    {
        return Utils::param('HTTP_CLIENT_IP', 'SERVER') ?? Utils::param('HTTP_X_FORWARDED_FOR', 'SERVER') ?? Utils::param('REMOTE_ADDR', 'SERVER');
    }

    /**
     * Get request header by key
     * 
     * @param string $key
     * @param boolean $lowercase
     * @return mixed
     */
    public static function requestHeader($key, $lowercase = true)
    {
        $headers = apache_request_headers();

        if ( $lowercase ) {
            $headers = array_flip($headers);
            $headers = array_map('strtolower', $headers);
            $headers = array_flip($headers);
            $key = strtolower($key);
        }

        return isset($headers[$key]) ? $headers[$key] : false;
    }
    
    /**
     * Create sef
     * 
     * @param string $val
     * @return string
     */
    public static function makeSefString($val)
    {
        $val = self::removeDiacritic($val, true);
        $val = str_replace(array(' '), array('-'), $val);
        $val = preg_replace("/-+/", '-', $val);
        $val = preg_replace("/[^\w\-]/u", '', $val);
        $val = trim($val, '-');
        $val = iconv("UTF-8", "UTF-8//IGNORE", $val);
        return $val;
    }
    
    /**
     * Return global variable
     * 
     * @param string $name
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    public static function param($name, $type = 'GET', $default = null)
    {
        return $GLOBALS['_' . strtoupper($type)][$name] ?? $default;
    }

}

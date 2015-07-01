<?php

namespace helpers;

class Utils 
{
    
    /**
     * Better var_dump
     */
    public static function pre_dump()
    {
        $data = func_get_args();
        echo '<pre>';
        foreach ($data AS $once)
        {
            var_dump($once);
        }
        echo '</pre>';
    }

    /**
     * Closing tags in html
     * 
     * @param string $text
     * @return string
     */
    public static function close_tags($text)
    {
        $patt_open = "%((?<!</)(?<=<)[\s]*[^/!>\s]+(?=>|[\s]+[^>]*[^/]>)(?!/>))%";
        $patt_close = "%((?<=</)([^>]+)(?=>))%";
        if (preg_match_all($patt_open,$text,$matches))
        {
            $m_open = $matches[1];
            if(!empty($m_open))
            {
                preg_match_all($patt_close,$text,$matches2);
                $m_close = $matches2[1];
                if (count($m_open) > count($m_close))
                {
                    $c_tags = array();
                    $m_open = array_reverse($m_open);
                    foreach ($m_close as $tag)
                    {
                        if ( ! empty($tag))
                        {
                            if ( ! isset($c_tags[$tag]))
                            {
                                $c_tags[$tag] = 0;
                            }

                            $c_tags[$tag]++;
                        }
                    }
                    foreach ($m_open as $k => $tag)
                    {
                        if (isset($c_tags[$tag]) AND $c_tags[$tag]-- <= 0)
                        {
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
     * Return size in bytes from upload_max_filesize ( 8M => 
     * 
     * @param string $val
     * @return int
     */
    public static function return_bytes( $val )
    {
        $val = trim( $val );
        $last = strtolower( $val[strlen( $val ) - 1] );
        switch ( $last )
        {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}

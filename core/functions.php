<?php
/**
 * Better var_dump
 */
function pre_dump()
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
function close_tags($text)
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
function removeDiacritic($text, $toLowerCase = false)
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
 * Shuffle array with keeping keys
 * 
 * @param array $array
 * @return boolean
 */
function shuffle_assoc(&$array)
{
    $keys = array_keys($array);

    shuffle($keys);

    foreach($keys as $key) {
        $new[$key] = $array[$key];
    }

    $array = $new;

    return true;
}
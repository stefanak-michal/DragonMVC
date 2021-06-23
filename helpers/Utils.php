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
    public static function closeTags(string $text): string
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
    public static function removeDiacritic(string $text, bool $toLowerCase = false): string
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
    public static function decodeBytes(string $val): int
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
    public static function encodeBytes(int $size): string
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
    public static function realIp(): string
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
    public static function requestHeader(string $key, bool $lowercase = true)
    {
        $headers = apache_request_headers();

        if ( $lowercase ) {
            $headers = array_flip($headers);
            $headers = array_map('strtolower', $headers);
            $headers = array_flip($headers);
            $key = strtolower($key);
        }

        return $headers[$key] ?? false;
    }
    
    /**
     * Create sef
     * 
     * @param string $val
     * @return string
     */
    public static function makeSefString(string $val): string
    {
        $val = self::removeDiacritic($val, true);
        $val = str_replace(array(' '), array('-'), $val);
        $val = preg_replace("/-+/", '-', $val);
        $val = preg_replace("/[^\w\-]/u", '', $val);
        $val = trim($val, '-');
        return iconv("UTF-8", "UTF-8//IGNORE", $val);
    }
    
    /**
     * Return global variable
     * 
     * @param string $name
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    public static function param(string $name, string $type = 'GET', $default = null)
    {
        return $GLOBALS['_' . strtoupper($type)][$name] ?? $default;
    }

    /**
     * Simple cURL GET or POST request
     *
     * @param string $url
     * @param array $data
     * @return mixed Returns false if request was not successful
     */
    public static function cURL(string $url, array $data = [])
    {
        $ch = curl_init();

        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        if (!empty($data)) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $data;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        if (!(curl_getinfo($ch, CURLINFO_RESPONSE_CODE) == 200 && curl_errno($ch) == 0))
            $response = false;
        curl_close($ch);

        return $response;
    }

    /**
     * Vrati referer
     *
     * @param ?\controllers\IController $controller
     * @param string $method
     * @param array $vars
     */
    public static function referer(?\controllers\IController &$controller = null, string &$method = '', array &$vars = [])
    {
        $ref = apache_request_headers()['Referer'] ?? '';
        if (empty($ref))
            return;
        
        $ref = str_ireplace(\core\Config::gi()->get('project_host'), '', $ref);
        $ref = parse_url($ref, PHP_URL_PATH);
        if (empty($ref))
            return;
        
        $cmv = \core\Router::gi()->findRoute($ref);
        if (empty($cmv))
            return;

        $dragon = new \core\Dragon();
        $reflection = new \ReflectionClass($dragon);
        $buildControllerName = $reflection->getMethod('buildControllerName');
        $buildControllerName->setAccessible(true);
        try {
            $controllerName = $buildControllerName->invoke($dragon, $cmv['controller']);
        } catch (\ReflectionException $e) {
            return;
        }

        $method = $cmv['method'];
        $vars = $cmv['vars'];
        $controller = new $controllerName();
    }

    /**
     * Append GET parameters to url
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function appendGetParams(string $url, array $params): string
    {
        $url = rtrim($url, ' ?&');
        return $url . (strpos($url, '?') > 0 ? '&' : '?') . http_build_query($params);
    }

    /**
     * Change string into snake_case from camelCase or PascalCase
     * @param string $str
     * @return string
     */
    public static function snake_case(string $str): string
    {
        return trim(preg_replace_callback("/[A-Z]/", function ($item) {
            return '_' . strtolower($item[0]);
        }, $str), '_');
    }

}

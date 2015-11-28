<?php

namespace helpers;

/**
 * Curl
 */
class Curl
{

    /**
     * Prototype user agent
     */
    const PROTOTYPE_USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.152 Safari/537.36';

    /**
     * Prototype cookie
     */
    const PROTOTYPE_COOKIE = 'TPL=1; CMDG=Confirmed=0&CountViews=1; x1=H8EnmhJc/OB9EoK6dEzRX1qxdpTznMiH1Z9lHFh35aY=; VZTX=226171731; GAS=0';

    /**
     * Pre debugovanie curl requestu
     *
     * @var boolean
     */
    private static $verbose = false;

    /**
     * Hlavicky zo zrealizovaneho requestu
     *
     * @var array
     */
    private static $headers = array();

    /**
     * cURL options
     *
     * @var array
     */
    private static $options = array();

    /**
     * Vyresetovanie nastaveni pre request
     * 
     * @return \static
     */
    public static function reset()
    {
        self::$headers = self::$options = array();
        self::$verbose = false;

        return new static;
    }

    /**
     * Nastavi vypis requestu do logu
     * 
     * @param boolean $verbose
     * @return \static
     */
    public static function setVerbose( $verbose = true )
    {
        self::$verbose = (bool) $verbose;

        return new static;
    }

    /**
     * Nastavenie cURL option
     * 
     * @param int $key cURL constant
     * @param mixed $value
     * @return \static
     */
    public static function setOption( $key, $value )
    {
        self::$options[$key] = $value;

        return new static;
    }

    /**
     * Nastavenie viacerych options naraz
     * 
     * @param array $options
     * @return \static
     */
    public static function setOptions( $options )
    {
        if ( is_array( $options ) )
        {
            foreach ( $options AS $key => $option )
            {
                self::setOption( $key, $option );
            }
        }

        return new static;
    }

    /**
     * Unset option
     * 
     * @param int $key
     * @return \static
     */
    public static function unsetOption( $key )
    {
        if ( is_array( $key ) )
        {
            array_map( array(__CLASS__, 'unsetOption'), $key );
        }
        else
        {
            unset( self::$options[$key] );
        }

        return new static;
    }

    /**
     * Vrati hlavicky z posledneho realizovaneho requestu
     * 
     * @return array
     */
    public static function getHeaders()
    {
        return self::$headers;
    }

    /**
     * Vykonanie requestu
     * 
     * @return string|boolean
     */
    public static function execute($url = '')
    {
        $ch = curl_init();
        
        if ( !empty($url) ) {
            self::setOption(CURLOPT_URL, $url);
        }
        
        curl_setopt_array( $ch, self::$options );

        $fp = false;
        if ( self::$verbose )
        {
            $fp = fopen( BASE_PATH . DS . 'tmp' . DS . 'curl.log', 'a' );
            if ( $fp !== false )
            {
                curl_setopt( $ch, CURLOPT_VERBOSE, true );
                curl_setopt( $ch, CURLOPT_STDERR, $fp );
            }
        }

        $response = curl_exec( $ch );
        self::$headers = curl_getinfo( $ch );
        curl_close( $ch );

        if ( self::$verbose && is_resource( $fp ) )
        {
            fclose( $fp );
        }

        return $response;
    }

}

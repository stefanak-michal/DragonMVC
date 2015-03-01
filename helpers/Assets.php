<?php

namespace helpers;

/**
 * 
 */
class Assets
{
    /**
     * Type css
     */
    const TYPE_CSS = 'css';
    /**
     * Type js
     */
    const TYPE_JS = 'js';
    
    /**
     * Router
     *
     * @var Router
     */
    private $router;
    
    /**
     * Assets to load on page
     *
     * @var array
     */
    private static $toLoad = array();
    
    /**
     * Construct
     * 
     * @param Router $router
     */
    public function __construct($router)
    {
        $this->router = $router;
    }
    
    /**
     * Reset list of assets to load
     */
    public function reset()
    {
        self::$toLoad = array();
    }
    
    /**
     * Add assets to load
     * 
     * @param string|array $name
     * @param string $type
     * @return Assets
     */
    public function add($name, $type)
    {
        if ( !is_array($name) ) {
            $name = array($name);
        }
        
        foreach ( $name AS $once ) {
            if ( !isset(self::$toLoad[$type][$once]) ) {
                $assetUrl = $this->router->getAssetUrl($once, $type);
                if ( !empty($assetUrl) ) {
                    self::$toLoad[$type][$once] = $assetUrl;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Render assets on page
     * 
     * @return string
     */
    public static function draw()
    {
        $output = array();
        
        if ( !empty(self::$toLoad[self::TYPE_CSS]) ) {
            foreach ( self::$toLoad[self::TYPE_CSS] AS $file ) {
                $output[] = '<link rel="stylesheet" type="text/css" href="' . $file . '" />';
            }
        }
        if ( !empty(self::$toLoad[self::TYPE_JS]) ) {
            foreach ( self::$toLoad[self::TYPE_JS] AS $file ) {
                $output[] = '<script type="text/javascript" src="' . $file . '" ></script>';
            }
        }
        
        return implode(PHP_EOL, $output);
    }
    
}

<?php

namespace helpers;

use core\Router;

/**
 * Assets
 * Helper for holding assets files to draw in html head
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
    private static $router;
    
    /**
     * Assets to load on page
     *
     * @var array
     */
    private static $toLoad = array();

    /**
     * Set Router instance
     * 
     * @param Router $router
     */
    public static function setRouter(Router $router)
    {
        self::$router = $router;
    }
    
    /**
     * Reset list of assets to load
     */
    public static function reset()
    {
        self::$toLoad = array();
    }
    
    /**
     * Add assets to load
     * 
     * @param string|array $name
     * @param string $type
     */
    public static function add($name, $type)
    {
        if ( ! self::$router instanceof Router ) {
            trigger_error('Set a instance of Router', E_USER_ERROR);
            return;
        }
        
        if ( !is_array($name) ) {
            $name = array($name);
        }
        
        foreach ( $name AS $once ) {
            if ( !isset(self::$toLoad[$type][$once]) ) {
                $assetUrl = self::$router->getAssetUrl($once, $type);
                if ( !empty($assetUrl) ) {
                    self::$toLoad[$type][$once] = $assetUrl;
                } else {
                    trigger_error('Generate asset url unsuccessful for ' . $once);
                }
            }
        }
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

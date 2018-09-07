<?php

namespace helpers;

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
     * Assets to load on page
     *
     * @var array
     */
    private static $toLoad = array();
    
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
        if ( !is_array($name) ) {
            $name = array($name);
        }
        
        foreach ( $name AS $once ) {
            if ( !isset(self::$toLoad[$type][$once]) ) {
                $assetUrl = self::generateUrl($once, $type);
                if ( !empty($assetUrl) ) {
                    self::$toLoad[$type][$once] = $assetUrl;
                } else {
                    trigger_error('Generate asset url unsuccessful for ' . $once);
                }
            }
        }
    }
    
    /**
     * Generate asset url
     * 
     * @param string $name
     * @param string $assetType
     * @param bool $absolute
     * @return string
     */
    private static function generateUrl($name, $assetType, $absolute = true)
    {
        $output = '';
        
        if ( !empty($name) AND !empty($assetType) ) {
            $files = (array) \core\Config::gi()->get($assetType);
            if ( array_key_exists($name, $files) ) {
                $output = ($absolute ? rtrim(\core\Router::gi()->getHost(), '/') : '') . '/assets/' . $assetType . '/' . $files[$name]['file'] . '?v=' . $files[$name]['version'];
            }
        }
        
        return $output;
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

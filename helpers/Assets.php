<?php

namespace helpers;

/**
 * Assets
 * Helper to manage css/js assets files
 * It use /config/assets.json
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
     * @var bool
     */
    private static $absoluteUrls = true;

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
    public static function add($name, string $type)
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
     * @return string
     */
    private static function generateUrl(string $name, string $assetType): string
    {
        $output = '';
        $assets = \core\Config::gi()->getJson('assets');

        if (isset($assets[$assetType][$name])) {
            if (self::$absoluteUrls)
                $output = \core\Router::gi()->getHost();
            
            $file = $assets[$assetType][$name];
            
            //auto add "min" on production if the file is available
            if (!IS_WORKSPACE && strpos($file, '.min.') === false) {
                $file = substr_replace($file, '.min.', strrpos($file, '.'), 1);
                if (!file_exists(BASE_PATH . DS . 'assets' . DS . $assetType . DS . $file))
                    $file = $assets[$assetType][$name];
            }

            //auto versioning
            $output .= 'assets/' . $assetType . '/' . $file . '?v=' 
                    . filemtime(BASE_PATH . DS . 'assets' . DS . $assetType . DS . $file);
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

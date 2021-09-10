<?php

namespace helpers;

/**
 * Assets
 * Helper to manage css/js assets files
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 * @package helpers
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
     * @param string ...$names relative path to css/js asset file in assets directory
     */
    public static function add(...$names)
    {
        foreach ($names as $name) {
            $type = pathinfo($name, PATHINFO_EXTENSION);
            if (!in_array($type, [self::TYPE_CSS, self::TYPE_JS])) {
                \core\Debug::var_dump('Unsupported asset type "' . $type . '" for asset file "' . $name . '"');
                continue;
            }

            if (!isset(self::$toLoad[$type][$name])) {
                $assetUrl = self::generateUrl($name);
                if (!empty($assetUrl)) {
                    self::$toLoad[$type][$name] = $assetUrl;
                } else {
                    \core\Debug::var_dump('Asset file "' . $name . '" not found');
                }
            }
        }
    }
    
    /**
     * Remove assets from load
     * @param string ...$names relative path to css/js asset file in assets directory
     */
    public static function remove(string ...$names)
    {
        foreach ($names as $name) {
            $type = pathinfo($name, PATHINFO_EXTENSION);
            if (array_key_exists($type, self::$toLoad) && array_key_exists($name, self::$toLoad[$type]))
                unset(self::$toLoad[$type][$name]);
        }
    }

    /**
     * Generate asset url
     *
     * @param string $name
     * @return string
     */
    private static function generateUrl(string $name): string
    {
        //auto add "min" on production if the file is available
        if (!IS_WORKSPACE && strpos($name, '.min.') === false) {
            $file = substr_replace($name, '.min.', strrpos($name, '.'), 1);
            if (file_exists(BASE_PATH . DS . 'assets' . DS . $file))
                $name = $file;
        }

        if (!file_exists(BASE_PATH . DS . 'assets' . DS . str_replace(['/', '\\'], DS, $name))) {
            return '';
        }

        $output = '';
        if (self::$absoluteUrls)
            $output = \core\Router::gi()->getHost();
        $output .= 'assets/' . $name . '?v=' . filemtime(BASE_PATH . DS . 'assets' . DS . $name);
        return $output;
    }

    /**
     * Render assets on page
     *
     * @return string
     */
    public static function draw(): string
    {
        $output = array();

        if (!empty(self::$toLoad[self::TYPE_CSS])) {
            foreach (self::$toLoad[self::TYPE_CSS] as $file) {
                $output[] = '<link rel="stylesheet" type="text/css" href="' . $file . '" />';
            }
        }
        if (!empty(self::$toLoad[self::TYPE_JS])) {
            foreach (self::$toLoad[self::TYPE_JS] as $file) {
                $output[] = '<script type="text/javascript" src="' . $file . '" ></script>';
            }
        }

        return implode(PHP_EOL, $output);
    }

}

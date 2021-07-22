<?php
/**
 * Autoload function
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */

spl_autoload_register(function ($name) {

    $parts = explode("\\", $name);
    $parts = array_filter($parts);

    //project directory
    $path = BASE_PATH . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    //core directory
    $path = DRAGON_PATH . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    //try to load namespaced path from vendor
    foreach ([BASE_PATH . DS . 'vendor', DRAGON_PATH . DS . 'vendor'] as $dir) {
        $path = $dir . DS . implode(DS, $parts) . '.php';
        if (file_exists($path)) {
            include_once $path;
            \core\Debug::files($path);
            return;
        }
    }

});

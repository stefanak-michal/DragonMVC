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

    /*
     * namespace calls
     */

    $path = BASE_PATH . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    $path = dirname(__DIR__) . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    //try to load namespaced path from vendor
    foreach ([BASE_PATH . DS . 'vendor', dirname(__DIR__) . DS . 'vendor'] as $dir) {
        $path = $dir . DS . implode(DS, $parts) . '.php';
        if (file_exists($path)) {
            include_once $path;
            \core\Debug::files($path);
            return;
        }
    }

    /*
     * non-namespace calls
     */

    $dirs = [];

    //check if caller is already from vendor directory and use this subdirectory
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    if (count($backtrace) == 2 && !empty($backtrace[1]['file']) && strpos($backtrace[1]['file'], 'vendor') !== false)
        $dirs[] = substr_replace($backtrace[1]['file'], '', strpos($backtrace[1]['file'], 'vendor') + strlen('vendor'));

    $dirs[] = BASE_PATH . DS . 'vendor';
    $dirs[] = dirname(__DIR__) . DS . 'vendor';

    foreach ($dirs as $path) {
        if (file_exists($path)) {
            //find requested file from vendor
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $file) {
                if (pathinfo($file, PATHINFO_FILENAME) == $parts[0]) {
                    include_once $file;
                    \core\Debug::files($file);
                    return;
                }
            }
        }
    }

});

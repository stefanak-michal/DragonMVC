<?php
/**
 * Autoload function
 * @author Michal Stefanak
 */

define('BASE_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($name) {
    $parts = explode("\\", $name);
    $parts = array_filter($parts);
    $cnt = count($parts) - 1;

    if ($cnt < 0) {
        trigger_error('Wtf?!', E_USER_ERROR);
        return;
    }

    /*
     * namespace calls
     */

    //compose standart namespaced path to file
    $path = BASE_PATH;
    foreach ($parts AS $i => $part) {
        if ($i == $cnt)
            $path .= DS . ucfirst($part) . '.php';
        else
            $path .= DS . $part;
    }

    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    //try to load namespaced path from vendor
    $path = BASE_PATH . DS . 'vendor' . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        include_once $path;
        \core\Debug::files($path);
        return;
    }

    /*
     * non-namespace calls
     */

    //check if caller is already from vendor directory and use this subdirectory
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    if (count($backtrace) == 2 && !empty($backtrace[1]['file']) && strpos($backtrace[1]['file'], 'vendor') !== false)
        $path = substr_replace($backtrace[1]['file'], '', strpos($backtrace[1]['file'], 'vendor') + strlen('vendor'));
    else
        $path = BASE_PATH . DS . 'vendor';

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
});


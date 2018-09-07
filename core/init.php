<?php
/**
 * Project initialization script
 * 
 */

define('BASE_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('IS_CLI', php_sapi_name() == 'cli');

/**
 * Autoload fnc
 */
spl_autoload_register(function($name) {
    $parts = explode("\\", $name);
    $parts = array_filter($parts);
    $cnt = count($parts) - 1;
    $tryVendor = true;

    if ( count($parts) >= 2 ) {
        $path = BASE_PATH;
        foreach ( $parts AS $i => $part ) {
            if ( $i == $cnt ) {
                $path .= DS . ucfirst($part) . '.php';
            } else {
                $path .= DS . $part;
            }
        }

        if ( file_exists($path) ) {
            include_once $path;
            $tryVendor = false;
        }
    }

    //in directory vendor we can have 3rd solutions
    if ( $tryVendor ) {
        $path = BASE_PATH . DS . 'vendor';

        foreach ( $parts AS $i => $part ) {
            $path .= DS . $part;
            if ( $i == $cnt ) {
                $path .= '.php';
            }
        }

        if ( file_exists($path) ) {
            include_once $path;
        }
    }
    
    if ( class_exists("\\core\\Debug") ) {
        \core\Debug::files($path);
    }
});

$workspace = false;

if ( IS_CLI ) {
    //console
    
    if ( empty($argv[1]) || !in_array($argv[1], array('production', 'development')) ) {
        exit('Wrong environment definition, check shell variable "env"');
    }
    
    $workspace = $argv[1] == 'development';
//    $_SERVER['SERVER_PORT'] = 80;
//    $_SERVER['HTTP_HOST'] = $workspace ? '' : '';
    
    set_time_limit(0);
} else {
    //website
    
    if ( isset($_SERVER['HTTP_HOST']) ) {
        $workspace = strpos($_SERVER['HTTP_HOST'], 'localhost') === 0 || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168') === 0 ?: false;
    }
}

define('IS_WORKSPACE', $workspace);

if (isset($GLOBALS['_GET']['debug'])) {
    $debug = $GLOBALS['_GET']['debug'] == 1;
} elseif (core\Config::gi()->get('debug') !== null) {
    $debug = core\Config::gi()->get('debug') == 1;
} else {
    $debug = IS_WORKSPACE;
}
define('DRAGON_DEBUG', $debug);

//Execute project
$app = new core\Dragon();
if ( !IS_CLI ) {
    $app->run();
}

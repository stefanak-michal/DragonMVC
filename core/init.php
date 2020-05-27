<?php
/**
 * Project initialization script
 * @author Michal Stefanak
 */

require_once "autoload.php";

$autorun = $autorun ?? true;
$workspace = false;

define('IS_CLI', php_sapi_name() == 'cli');
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

if (\core\Config::gi()->get('debug') !== null) {
    $debug = \core\Config::gi()->get('debug') == 1;
} else {
    $debug = IS_WORKSPACE;
}
define('DRAGON_DEBUG', $debug);

//Execute project
$app = new core\Dragon();
if ( !IS_CLI && $autorun ) {
    $app->run();
}

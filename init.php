<?php
/**
 * Project initialization script
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */

const DS = DIRECTORY_SEPARATOR;

if (!defined('BASE_PATH')) {
    if (array_key_exists('SCRIPT_FILENAME', $_SERVER)) {
        define('BASE_PATH', pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME));
    } else {
        define('BASE_PATH', __DIR__);
    }
}

require_once 'core' . DS . 'autoload.php';

$autorun = $autorun ?? true;
$workspace = false;

define('IS_CLI', php_sapi_name() == 'cli');
if ( IS_CLI ) {
    //console
    if (empty($argv[1])) {
        $argv[1] = 'production';
        echo 'Environment not defined, running as production' . PHP_EOL;
    }

    $argv[1] = strtolower($argv[1]);
    if (!in_array($argv[1], array('production', 'development', 'prod', 'dev'))) {
        exit('Wrong environment definition');
    }
    
    $workspace = in_array($argv[1], ['development', 'dev']);
    set_time_limit(0);
} else {
    //website
    if ( isset($_SERVER['HTTP_HOST']) ) {
        $workspace = strpos($_SERVER['HTTP_HOST'], 'localhost') === 0
            || $_SERVER['HTTP_HOST'] == '127.0.0.1'
            || in_array(getenv('DRAGON_ENV'), ['dev', 'development', 'workspace'], true);
    }
}

define('IS_WORKSPACE', $workspace);

if (\core\Config::gi()->get('debug') !== null) {
    $debug = \core\Config::gi()->get('debug') == 1;
} else {
    $debug = IS_WORKSPACE;
}
define('DRAGON_DEBUG', $debug);

/**
 * Dragon debug - simple alias for \dragon\Debug::var_dump
 * @param mixed ...$vars
 */
function dump(...$vars)
{
    \core\Debug::var_dump(...$vars);
}

//Execute project
$app = new \core\Dragon();
if ( !IS_CLI && $autorun ) {
    $app->run();
}

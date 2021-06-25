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

if (!defined('DRAGON_PATH')) {
    define('DRAGON_PATH', __DIR__);
}

require_once 'core' . DS . 'autoload.php';

$autorun = $autorun ?? true;
$workspace = false;

if (file_exists(BASE_PATH . DS . 'config' . DS . 'development' . DS) || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']))
    $workspace = true;
define('IS_WORKSPACE', $workspace);

define('IS_CLI', php_sapi_name() == 'cli');
if (IS_CLI) {
    set_time_limit(0);
}

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

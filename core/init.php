<?php
define('BASE_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('IS_WORKSPACE', $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' ?: false);

include_once BASE_PATH . DS . 'core' . DS . 'Dragon.php';
include_once BASE_PATH . DS . 'core' . DS . 'DB.php';
$app = new core\Dragon();
spl_autoload_register(array($app, 'autoload'));
$app->run();
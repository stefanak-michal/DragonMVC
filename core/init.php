<?php
define('BASE_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('IS_WORKSPACE', $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' ?: false);

include_once BASE_PATH . DS . 'core' . DS . 'framework.php';
$framework = new core\Framework();
spl_autoload_register(array($framework, 'autoload'));
$framework->run();
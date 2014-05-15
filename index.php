<?php
define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', __DIR__);

include_once BASE_PATH . DS . 'core' . DS . 'framework.php';
Framework::gi()->run();
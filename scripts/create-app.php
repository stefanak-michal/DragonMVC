<?php

/**
 * This scripts allows you to generate base application directory structure with basic files
 * Run in through terminal and as a first argument enters path to target directory
 *
 * @author Michal Stefanak
 * @package scripts
 * @link https://github.com/stefanak-michal/DragonMVC
 */

if ($argc != 2) {
    echo 'You have to enter target path for your app as argument';
    exit;
}

if (file_exists($argv[1])) {
    if (!is_dir($argv[1]) || count(scandir($argv[1])) > 2) {
        echo 'Target path has to be empty directory';
        exit;
    }
}

//realpath works only on existing path
if (!file_exists($argv[1]))
    mkdir($argv[1], 0777, true);
define('BASE_PATH', realpath($argv[1]));

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init.php';

$dirs = [
    'assets',
    'assets' . DS . 'css',
    'assets' . DS . 'js',
    'assets' . DS . 'img',
    'components',
    'config',
    'config' . DS . 'production',
    'config' . DS . 'development',
    'controllers',
    'helpers',
    'models',
    'scripts',
    'vendor',
    'views',
    'views' . DS . 'homepage',
];

foreach ($dirs as $dir) {
    if (!mkdir(BASE_PATH . DS . $dir)) {
        echo 'Cannot create directory at ' . BASE_PATH . DS . $dir;
        exit;
    }
}

//index
$index = <<<'EOD'
<?php

$path = '';
$paths = [
    getenv('DRAGON_PATH'),
    __DIR__ . DIRECTORY_SEPARATOR . 'dragonmvc',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dragonmvc'
];
foreach ($paths as $entry) {
    $entry = rtrim($entry, '/\\');
    if (!empty($entry) && file_exists($entry) && is_dir($entry)) {
        $path = $entry;
        break;
    }
}

if (empty($path))
    exit('DragonMVC core not found. Define global system variable DRAGON_PATH with path to it.');

define('DRAGON_PATH', $path);
define('BASE_PATH', __DIR__);

include_once DRAGON_PATH . DIRECTORY_SEPARATOR . 'init.php';

EOD;
file_put_contents(BASE_PATH . DS . 'index.php', $index);

//config files
file_put_contents(BASE_PATH . DS . 'config' . DS . 'main.cfg.php', '<?php
$aConfig = [
    \'defaultController\' => \'Homepage\',
    \'defaultMethod\' => \'index\',
];
');
file_put_contents(BASE_PATH . DS . 'config' . DS . 'production' . DS . 'main.cfg.php', '<?php
$aConfig = [];
');
file_put_contents(BASE_PATH . DS . 'config' . DS . 'development' . DS . 'main.cfg.php', '<?php
$aConfig = [
    \'project_host\' => \'http://localhost/' . basename(BASE_PATH) . '\'
];
');

//routes
$config = <<<'EOD'
<?php
/**
 * Routes specification
 * 
 * allowed variables in mask:
 * %i - integer
 * %d - double (with dot separator)
 * %s - any string (default regex [\w\-]+)
 *
 * @link https://github.com/stefanak-michal/DragonMVC/wiki/Routing
 */
$aConfig = [
    'routes' => [
        '/' => 'homepage/index',
    ]
];

EOD;
file_put_contents(BASE_PATH . DS . 'config' . DS . 'routes.cfg.php', $config);


//controller
file_put_contents(BASE_PATH . DS . 'controllers' . DS . 'Homepage.php', '<?php

namespace controllers;

/**
 * Class Homepage
 * @package controllers
 */
class Homepage implements IController
{

    public function index()
    {
        \core\View::gi()->set("msg", "Hello ' . basename(BASE_PATH) . '!");
    }
    
    public function beforeMethod()
    {
 
    }
    
    public function afterMethod()
    {
        $content = \Core\View::gi()->render();
        $pos = strrpos($content, "</body>");
        if (IS_WORKSPACE && $pos !== false)
            $content = substr_replace($content, \core\Debug::onsite(), $pos, 0);
        echo $content;
    }
}
');

//view
$view = <<<'EOD'
<!DOCTYPE html>
<html>
<head></head>
<body>
    <h1><?= $msg ?></h1>
</body>
</html>

EOD;
file_put_contents(BASE_PATH . DS . 'views' . DS . 'homepage' . DS . 'index.phtml', $view);

//gitignore
file_put_contents(BASE_PATH . DS . '.gitignore', '/tmp/
.htaccess
.idea
/config/development/
');

//htaccess
file_put_contents(BASE_PATH . DS . '.htaccess', 'RewriteEngine On
RewriteBase /' . basename(BASE_PATH) . '/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]');

copy(__DIR__ . DS . '.htaccess', BASE_PATH . DS . 'scripts' . DS . '.htaccess');

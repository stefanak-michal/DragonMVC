<?php
define('BASE_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

//autoload fnc
spl_autoload_register(function($name) {
    $parts = explode("\\", $name);
    $parts = array_filter($parts);

    if ( count($parts) >= 2 ) {
        $path = BASE_PATH;
        foreach ( $parts AS $i => $part ) {
            if ( $i == 0 ) {
                $path .= DS . $part;
            } elseif ( $i == count($parts) - 1 ) {
                $path .= DS . ucfirst($part) . '.php';
            } else {
                $path .= DS . $part;
            }
        }

        if ( file_exists($path) ) {
            include_once $path;
        }
    }

    //pripadne pridat nejake nacitanie z noveho priecinka "lib", kde budu bez namespace 3rd kniznice 
    //kniznica by ale tiez mohla byt samostatny namespace, takze asi upravit hornu cast a pridat druhy pokus na priecinok "lib"
    //pripadne pridat vynimku na "DB", aby sa nemusel samostatne includovat ..alebo ju skusit prehodit na namespace
    
});

//DB cls is not namespace
include_once BASE_PATH . DS . 'core' . DS . 'DB.php';

if ( php_sapi_name() == 'cli' ) {
    //console
    
    if ( empty($argv[1]) || !in_array($argv[1], array('production', 'development')) ) {
        exit('Wrong environment definition, check shell variable "env"');
    }
    
    define('IS_WORKSPACE', $argv[1] == 'development');
    
    $app = new core\Dragon();
    
} else {
    //website
    
    define('IS_WORKSPACE', $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'local.zelania.sk' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168') === 0 ?: false);
    
    $app = new core\Dragon();
    $app->run();
    
}

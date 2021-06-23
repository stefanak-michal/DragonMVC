<?php

namespace core;

/**
 * Config
 * Read config files and hold it
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Config
{

    /**
     * Array of all config variables
     *
     * @var array
     */
    private $configVars = array();

    /**
     * Array of lookup tables
     *
     * @var array
     */
    private $lookUpTables = array();

    /**
     * Array of loaded json files
     * @var array
     */
    private $jsonFiles = [];

    /**
     * Lookuptable file affix
     *
     * @var string
     */
    public static $ltAffix = '.lt.php';

    /**
     * Config file affix
     *
     * @var string
     */
    public static $cfgAffix = '.cfg.php';

    /**
     * JSON config affix
     *
     * @var string
     */
    public static $jsonAffix = '.json';
    
    /**
     * @var Config
     */
    private static $instance;
    
    /**
     * Singleton
     * 
     * @return Config
     */
    public static function gi(): Config
    {
        if (self::$instance == null)
            self::$instance = new Config();
        
        return self::$instance;
    }
    
    /**
     * Construct
     */
    public function __construct()
    {
        //read main config files
        $this->loadLookuptable('main');
        $this->loadConfig('main');
        $this->loadConfig('routes');
    }

    /**
     * Load lookuptable file
     * 
     * @param string $name
     */
    public function loadLookuptable(string $name)
    {
        $this->loadFiles($name . self::$ltAffix, 'lookUpTable', 'lookUpTables');
    }

    /**
     * Load config file
     * 
     * @param string $name
     */
    public function loadConfig(string $name)
    {
        $this->loadFiles($name . self::$cfgAffix);
    }

    /**
     * Load json config
     * 
     * @param string $file
     * @param boolean $assoc
     * @return array
     */
    public function getJson(string $file, bool $assoc = true): array
    {
        if (array_key_exists($file, $this->jsonFiles))
            return $this->jsonFiles[$file];

        $filename = BASE_PATH . DS . 'config' . DS . $file . self::$jsonAffix;
        if ( file_exists($filename) ) {
            $content = file_get_contents($filename);
            $this->jsonFiles[$file] = json_decode($content, $assoc);

            if ( json_last_error() != JSON_ERROR_NONE ) {
                $this->jsonFiles[$file] = [];
            } else {
                Debug::files($filename);
            }
        }

        return $this->jsonFiles[$file];
    }

    /**
     * Load config files
     * 
     * @param string $path
     * @param string $variable
     * @param string $objVar
     */
    private function loadFiles(string $path, string $variable = 'aConfig', string $objVar = 'configVars')
    {
        $files = [
            dirname(__DIR__) . DS . 'config' . DS . $path,
            dirname(__DIR__) . DS . 'config' . DS . (IS_WORKSPACE ? 'development' : 'production') . DS . $path,
            BASE_PATH . DS . 'config' . DS . $path,
            BASE_PATH . DS . 'config' . DS . (IS_WORKSPACE ? 'development' : 'production') . DS . $path
        ];

        foreach ( $files AS $file ) {
            if (!file_exists($file))
                continue;

            Debug::files($file);
            include $file;

            if ( !empty($$variable) ) {
                foreach ( $$variable AS $key => $value ) {
                    $this->{$objVar}[$key] = $value;
                }
            }

            unset($$variable);
        }
    }

    /**
     * Set config parameter
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        if ( !empty($key) ) {
            if ( !empty($value) ) {
                $this->configVars[$key] = $value;
            } else {
                unset($this->configVars[$key]);
            }
        }
    }

    /**
     * Read config parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $output = $default;

        if ( !empty($key) AND isset($this->configVars[$key]) ) {
            $output = $this->configVars[$key];
        }

        return $output;
    }

    /**
     * Read lookup table value
     * 
     * @param string $dotSeparatedKeys
     * @return mixed
     */
    public function lt(string $dotSeparatedKeys)
    {
        if (empty($dotSeparatedKeys))
            return false;

        $output = array();

        foreach (explode('.', $dotSeparatedKeys) AS $key) {
            if (empty($output) && array_key_exists($key, $this->lookUpTables)) {
                $output = $this->lookUpTables[$key];
            } elseif (is_array($output) && array_key_exists($key, $output)) {
                $output = $output[$key];
            } else {
                return false;
            }
        }

        return $output;
    }

    /**
     * Apply config settings by key on object
     * @param string $configKey
     * @param $object
     */
    public static function apply(string $configKey, $object)
    {
        $c = \core\Config::gi()->get($configKey);
        if (!empty($c) && is_array($c)) {
            foreach ($c as $key => $value) {
                if (is_int($key) && method_exists($object, $value)) {
                    call_user_func([$object, $value]);
                } elseif (property_exists($object, $key)) {
                    if (is_object($object))
                        $object->{$key} = $value;
                    else
                        $object::$$key = $value;
                }
            }
        }
    }

}

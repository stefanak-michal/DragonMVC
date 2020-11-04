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
    public static function gi()
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
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . $name . self::$ltAffix, 'lookUpTable', 'lookUpTables');
    }

    /**
     * Load config file
     * 
     * @param string $name
     */
    public function loadConfig(string $name)
    {
        $this->loadFiles($name . self::$cfgAffix);
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . $name . self::$cfgAffix);

        if ( IS_WORKSPACE ) {
            $this->loadFiles('.' . $name . self::$cfgAffix);
        }
    }

    /**
     * Load json config
     * 
     * @param string $file
     * @param boolean $assoc
     * @return array
     */
    public function getJson(string $file, $assoc = true)
    {
        $json = array();

        $file = BASE_PATH . DS . 'config' . DS . $file . self::$jsonAffix;
        if ( file_exists($file) ) {
            $content = file_get_contents($file);
            $json = json_decode($content, $assoc);

            if ( json_last_error() != JSON_ERROR_NONE ) {
                $json = array();
            }
        }

        return $json;
    }

    /**
     * Load config files
     * 
     * @param string $path
     * @param string $variable
     * @param string $objVar
     */
    private function loadFiles(string $path, $variable = 'aConfig', $objVar = 'configVars')
    {
        $files = glob(BASE_PATH . DS . 'config' . DS . $path);
        $files = array_filter($files);

        if ( !empty($files) ) {
            foreach ( $files AS $file ) {
                include $file;
                if ( !empty($$variable) ) {
                    foreach ( $$variable AS $key => $value ) {
                        $this->{$objVar}[$key] = $value;
                        //$this->set($key, $value);
                    }
                }
                unset($$variable);
            }
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

}

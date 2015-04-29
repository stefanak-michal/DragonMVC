<?php

namespace core;

/**
 * Config
 * 
 * Read config files and hold it
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
    private $ltAffix = '.lt.php';
    /**
     * Config file affix
     *
     * @var string
     */
    private $cfgAffix = '.cfg.php';
    
    /**
     * Construct
     */
    public function __construct()
    {
        //read main config files
        $this->loadLookuptable('main');
        $this->loadConfig('assets');
        $this->loadConfig('main');
        $this->loadConfig('routes');
    }
    
    /**
     * Load lookuptable file
     * 
     * @param string $name
     */
    public function loadLookuptable($name)
    {
        $this->loadFiles($name . $this->ltAffix, 'lookUpTable', 'lookUpTables');
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . $name . $this->ltAffix, 'lookUpTable', 'lookUpTables');
    }
    
    /**
     * Load config file
     * 
     * @param string $name
     */
    public function loadConfig($name)
    {
        $this->loadFiles($name . $this->cfgAffix);
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . $name . $this->cfgAffix);
    }
    
    /**
     * Load config files
     * 
     * @param string $path
     * @param string $variable
     * @param string $objVar
     */
    private function loadFiles($path, $variable = 'aConfig', $objVar = 'configVars')
    {
        $files = glob(BASE_PATH . DS . 'config' . DS . $path);
        $files = array_filter($files);
        if ( !empty($files) ) {
            foreach ($files AS $file) {
                include $file;
                if ( !empty($$variable) ) {
                    foreach ($$variable AS $key => $value) {
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
    public function set($key, $value)
    {
        if ( ! empty($key))
        {
            if ( ! empty($value))
            {
                $this->configVars[$key] = $value;
            }
            else
            {
                unset($this->configVars[$key]);
            }
        }
    }
    
    /**
     * Read config parameter
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $output = null;
        
        if ( ! empty($key) AND isset($this->configVars[$key]))
        {
            $output = $this->configVars[$key];
        }
        
        return $output;
    }
    
    /**
     * Read lookup table value
     * 
     * @param string $dotSeparatedKeys
     * @return array
     */
    public function lt($dotSeparatedKeys)
    {
        $output = array();
        
        if ( ! empty($dotSeparatedKeys))
        {
            $keys = explode('.', $dotSeparatedKeys);
            if ( ! empty($keys))
            {
                foreach ($keys AS $key)
                {
                    if (empty($output) AND isset($this->lookUpTables[$key]))
                    {
                        $output = $this->lookUpTables[$key];
                    }
                    elseif (isset($output[$key]))
                    {
                        $output = $output[$key];
                    }
                }
            }
        }
        else
        {
            $output = $this->lookUpTables;
        }
        
        return $output;
    }
    
}
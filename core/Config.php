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
     * Construct
     */
    public function __construct()
    {
        //read all config files
        $this->loadFiles('*.cfg.php', 'aConfig', 'configVars');
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . '*.cfg.php', 'aConfig', 'configVars');
        
        //read all lookup table files
        $this->loadFiles('*.lt.php', 'lookUpTable', 'lookUpTables');
        $this->loadFiles((IS_WORKSPACE ? 'development' : 'production') . DS . '*.lt.php', 'lookUpTable', 'lookUpTables');
    }
    
    /**
     * Load config files
     * 
     * @param string $path
     * @param string $variable
     * @param string $objVar
     */
    private function loadFiles($path, $variable, $objVar)
    {
        $files = glob(BASE_PATH . DS . 'config' . DS . $path);
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
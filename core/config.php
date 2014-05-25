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
        $configFiles = glob(BASE_PATH . DS . 'config' . DS . '*.cfg.php');
        if ( ! empty($configFiles))
        {
            foreach ($configFiles AS $file)
            {
                include $file;
                
                if (isset($aConfig) AND ! empty($aConfig))
                {
                    foreach ($aConfig AS $key => $value)
                    {
                        $this->set($key, $value);
                    }
                }
                
                unset($aConfig);
            }
            unset($file, $variables, $key, $value);
        }
        unset($configFiles);

        //read all lookup table files
        $ltFiles = glob(BASE_PATH . DS . 'config' . DS . '*.lt.php');
        if ( ! empty($ltFiles))
        {
            foreach ($ltFiles AS $file)
            {
                include $file;
                
                if (isset($lookUpTable) AND ! empty($lookUpTable))
                {
                    foreach ($lookUpTable AS $key => $value)
                    {
                        $this->lookUpTables[$key] = $value;
                    }
                }
                
                unset($lookUpTable);
            }
            unset($file, $key, $value);
        }
        unset($ltFiles);
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
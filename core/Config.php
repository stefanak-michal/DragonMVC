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
        if (!(self::$instance instanceof self)) {
            self::$instance = new Config();

            $names = [];
            $fn = function (string $file) use (&$names) {
                if (substr($file, -strlen(self::$cfgAffix)) == self::$cfgAffix) {
                    self::$instance->loadFile(pathinfo($file, PATHINFO_BASENAME));
                    $names[] = pathinfo($file, PATHINFO_BASENAME);
                } elseif (substr($file, -strlen(self::$ltAffix)) == self::$ltAffix) {
                    self::$instance->loadFile(pathinfo($file, PATHINFO_BASENAME), 'lookUpTables');
                    $names[] = pathinfo($file, PATHINFO_BASENAME);
                }
            };

            foreach (glob(DRAGON_PATH . DS . 'config' . DS . '*.php') as $file) {
                $fn($file);
            }

            foreach (glob(BASE_PATH . DS . 'config' . DS . '*.php') as $file) {
                if (in_array(pathinfo($file, PATHINFO_BASENAME), $names))
                    continue;
                $fn($file);
            }
        }

        return self::$instance;
    }

    /**
     * Load config files
     *
     * @param string $filename
     * @param string $objVar
     */
    private function loadFile(string $filename, string $objVar = 'configVars')
    {
        $files = [
            DRAGON_PATH . DS . 'config' . DS . $filename,
            DRAGON_PATH . DS . 'config' . DS . (IS_WORKSPACE ? 'development' : 'production') . DS . $filename,
            BASE_PATH . DS . 'config' . DS . $filename,
            BASE_PATH . DS . 'config' . DS . (IS_WORKSPACE ? 'development' : 'production') . DS . $filename
        ];

        foreach ($files as $file) {
            if (!file_exists($file))
                continue;

            Debug::files($file);

            (function () use ($file, $objVar) {
                include $file;
                $defined = get_defined_vars();
                unset($defined['file'], $defined['objVar']);
                $this->{$objVar} = array_replace_recursive($this->{$objVar}, reset($defined));
            })();
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
        if (!empty($key)) {
            if (!empty($value)) {
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

        if (!empty($key) and isset($this->configVars[$key])) {
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
            return null;

        $output = array();

        foreach (explode('.', $dotSeparatedKeys) as $key) {
            if (empty($output) && array_key_exists($key, $this->lookUpTables)) {
                $output = $this->lookUpTables[$key];
            } elseif (is_array($output) && array_key_exists($key, $output)) {
                $output = $output[$key];
            } else {
                return null;
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

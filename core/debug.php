<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * Debug
 * 
 * Developer tools
 * @todo Think about if we needed construct and gi()
 */
final class Debug
{
    /**
     * Instance of class
     *
     * @static
     * @var Debug
     */
    protected static $instance;
    
    /**
     * Construct
     */
    public function __construct()
    {
        
    }
    
    /**
     * Get class instance
     * 
     * @return object
     * @static
     */
    public static function gi() 
    {
        if( self::$instance === NULL )
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Dump data to html file
     * 
     * @static
     */
    public static function var_dump()
    {
        $args = func_get_args();
        
        if ( ! empty($args))
        {
            ob_start();
            
            foreach ($args AS $one)
            {
                var_dump($one);
            }
            
            $content = ob_get_clean();
            
            if ( ! file_exists('debug.html'))
            {
                fopen('debug.html', 'w');
            }
            
            $file = './temp/debug-var_dump.html';
            
            $data = file_get_contents($file);
            file_put_contents($file, '<b>' . date('Y-m-d H:i:s') . '</b><br>' . $content . '<br><br>' . $data);
        }
    }
    
}
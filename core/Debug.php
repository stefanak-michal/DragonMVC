<?php

/**
 * Debug
 * 
 * Developer tools
 * @todo Do it better
 */
final class Debug
{
    /**
     * Construct
     */
    public function __construct()
    {
        
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
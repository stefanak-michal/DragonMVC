<?php

namespace core;

use \Exception;

/**
 * Debug
 * 
 * Developer tools
 */
final class Debug
{

    /**
     * @internal public for Generator usage
     * @var array
     */
    public static $tables = [];
    
    /**
     * @var array
     */
    private static $timers = [];

    /**
     * Dump data
     */
    public static function var_dump()
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        $args = func_get_args();

        if ( !empty($args) ) {
            foreach ( $args AS $one ) {
                $e = new Exception();
                $backtrace = preg_split("/[\r\n]+/", $e->getTraceAsString());
                
                ob_start();
                var_dump($one);
                $content = ob_get_clean();

                self::$tables[__FUNCTION__][] = ['dump' => '<div class="collapsable">' . $content . '</div>' . '<div>' . implode('<br>', $backtrace) . '</div>'];
            }
        }
    }
    
    /**
     * List of loaded files
     * 
     * @param string $file
     */
    public static function files($file)
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        $e = new Exception();
        $backtrace = preg_split("/[\r\n]+/", $e->getTraceAsString());
        
        $exists = file_exists($file);
        
        $html = '<div class="collapsable ' . ($exists ? '' : 'red') . '">' . $file . '</div>';
        $html .= '<div>' . implode('<br>', $backtrace) . '</div>';
        
        self::$tables[__FUNCTION__][] = ['file' => $html, 'size (bytes)' => $exists ? filesize($file) : 0];
    }
    
    /**
     * Measure time
     * 
     * @param string $key
     */
    public static function timer($key)
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        if ( !isset(self::$timers[$key]) ) {
            self::$timers[$key] = microtime(true);
        } else {
            $e = new Exception();
            $backtrace = preg_split("/[\r\n]+/", $e->getTraceAsString());
            
            self::$tables[__FUNCTION__][] = [
                'key' => '<div class="collapsable">' . $key . '</div>' . '<div>' . implode('<br>', $backtrace) . '</div>', 
                'time (msec)' => sprintf('%f', (microtime(true) - self::$timers[$key]) * 1000)
            ];
            unset(self::$timers[$key]);
        }
    }
    
    /**
     * Database queries
     * 
     * @todo add query explain
     * @param array $args
     */
    public static function query($args)
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        if ( isset($args['query'], $args['explain'][0]) && is_array($args['explain']) ) {
            $html = '<div><table cellspacing="0"><thead><tr>';
            
            foreach ( array_keys($args['explain'][0]) AS $key ) {
                $html .= '<th>' . $key . '</th>';
            }
            
            $html .= '</tr></thead><tbody>';
            
            foreach ( $args['explain'] AS $row ) {
                $html .= '<tr>';
                foreach ( $row AS $value ) {
                    $html .= '<td>' . $value . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table></div>';
            
            $args['query'] = '<div class="collapsable">' . $args['query'] . '</div>';
            $args['query'] .= $html;
        }
        
        unset($args['explain']);
        self::$tables[__FUNCTION__][] = $args;
    }

}

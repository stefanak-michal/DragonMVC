<?php

namespace core;

/**
 * Debug
 * 
 * Developer tools
 * @todo Do it better
 */
final class Debug
{

    /**
     * @var array
     */
    private static $tables = [];
    
    /**
     * @var array
     */
    private static $timers = [];

    /**
     * Dump data
     * 
     * @todo add backtrace
     */
    public static function var_dump()
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        $args = func_get_args();

        if ( !empty($args) ) {
            foreach ( $args AS $one ) {
                //@todo change var_dump to something better, maybe own?
                ob_start();
                var_dump($one);
                $content = ob_get_clean();

                //@todo add some backtrace or something
                self::$tables[__FUNCTION__][] = [$content];
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
        
        self::$tables[__FUNCTION__][] = ['file' => $file, 'size' => file_exists($file) ? filesize($file) : 0];
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
            self::$tables[__FUNCTION__][] = ['key' => $key, 'time (sec)' => round(microtime(true) - self::$timers[$key], 6)];
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
        
        self::$tables[__FUNCTION__][] = $args;
    }

    /**
     * Generate debug html file from collected data
     */
    public static function generate()
    {
        if ( !DRAGON_DEBUG ) {
            return;
        }
        
        $path = BASE_PATH . DS . 'tmp' . DS . 'debug' . DS;
        if ( !file_exists($path) ) {
            mkdir($path, 0777, true);
        }
        
        //clear old files
        if ( file_exists($path . 'last.html') ) {
            unlink($path . 'last.html');
        }
        $files = glob($path . '*.html');
        if ( count($files) >= 10 ) {
            rsort($files);
            for ( $i = count($files) - 1; $i >= 10; $i-- ) {
                unlink($files[$i]);
                unset($files[$i]);
            }
        }
        
        ob_start();
        
        echo implode(PHP_EOL, ['<!DOCTYPE html>', '<html>', '<head>']), PHP_EOL;
        self::htmlStyles();
        self::htmlScripts();
        echo implode(PHP_EOL, ['</head>', '<body>']), PHP_EOL;
        if ( !empty($_SERVER['REQUEST_URI']) ) {
            echo 'URI: <b>' . $_SERVER['REQUEST_URI'] . '</b><br><br>', PHP_EOL;
        }
        
        //tabs switches
        $tabs = [];
        $class = 'active';
        foreach ( array_keys(self::$tables) AS $key ) {
            $tabs[] = '<li class="' . $class . '" data-tab="' . $key . '">' . $key . ' (' . count(self::$tables[$key]) . ')</li>';
            $class = '';
        }
        echo '<ul>' . implode('', $tabs) . '</ul>', PHP_EOL;
        
        self::htmlTables();
        self::htmlDebugHistory($files);
        
        echo implode(PHP_EOL, ['</body>', '</html>']), PHP_EOL;
        $html = ob_get_clean();

        $filename = microtime(true) . '.html';
        file_put_contents($path . $filename, $html);
        file_put_contents($path . 'last.html', $html);
        
        self::$tables = [];
    }
    
    /**
     * @param array $files
     */
    private static function htmlDebugHistory($files)
    {
        echo '<div class="history">';
        echo '<b>History:</b><br>';
        foreach ( $files AS $file ) {
            $file = substr($file, strrpos($file, DS) + 1);
            $url = Dragon::$host . 'tmp/debug/' . $file;
            echo '<a href="' . $url . '">' . $url . '</a>';
        }
        echo '</div>';
    }
    
    private static function htmlStyles()
    {
        echo '<style type="text/css">
            ul { margin: 0; padding: 0 0 10px; } 
            ul li { display: inline-block; border-bottom: 1px solid silver; padding: 4px 10px; cursor: pointer; } 
            ul li:hover { background-color: #eee; }
            ul li.active { border: 1px solid silver; border-bottom: 0; }
            table { display: none; width: 100%; } 
            table.active { display: table; } 
            table tr > * { border-bottom: 1px solid silver; border-left: 1px solid silver; } 
            table tr *:first-child { border-left: 0; } 
            table tr td {border-bottom: 1px solid silver; padding: 4px 6px; }
            table tfoot tr td { border: 0; margin-top: 4px; color: gray; }
            .history { margin-top: 50px; }
            .history a { display: block; }
        </style>', PHP_EOL;
    }
    
    private static function htmlScripts()
    {
        echo '<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
        <script>
            $(document).ready(function() {
                $("ul li").on("click", function() {
                    $("ul li, table").removeClass("active");
                    $(this).addClass("active");
                    $("table#" + $(this).data("tab")).addClass("active");
                });
            });
        </script>', PHP_EOL;
    }
    
    private static function htmlTables()
    {
        //tabs with tables
        $class = 'active';
        foreach ( self::$tables AS $key => $table ) {
            echo '<table class="' . $class . '" id="' . $key . '" cellspacing="0">';
            $class = '';
            
            //thead - if first table row keys is not numeric
            if ( !is_numeric(key($table[0])) ) {
                echo '<thead><tr>';
                echo '<th>N.</th>';
                foreach ( array_keys($table[0]) AS $columnKey ) {
                    echo '<th>' . $columnKey . '</th>';
                }
                echo '</tr></thead>';
            }
            
            $doFooter = count($table) > 1;
            if ( $doFooter ) {
                $footer = [];
            }
            
            //tbody rows
            $i = 1;
            echo '<tbody>';
            foreach ( $table AS $row ) {
                echo '<tr>';
                echo '<td>' . $i . '</td>';
                foreach ( $row AS $cellKey => $cell ) {
                    echo '<td>' . $cell . '</td>';
                    
                    if ( $doFooter ) {
                        $footer[$cellKey][] = $cell;
                    }
                }
                echo '</tr>';
                $i++;
            }
            echo '</tbody>';
            
            //footer
            if ( $doFooter ) {
                echo '<tfoot><tr>';
                echo '<td></td>';
                foreach ( $footer AS $vals ) {
                    echo '<td>';
                    $tmp = array_filter($vals, 'is_numeric');
                    if ( count($tmp) == count($vals) ) {
                        echo array_sum($vals);
                    }
                    echo '</td>';
                }
                echo '</tr></tfoot>';
            }
            
            echo '</table>', PHP_EOL;
        }
    }

}

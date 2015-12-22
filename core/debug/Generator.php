<?php

namespace core\debug;

use core\Dragon,
    core\Debug;

/**
 * Generator
 */
final class Generator
{
    
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
        
        self::history($files);
        $time = microtime(true);
        
        ob_start();
        
        self::echoHtml('<!DOCTYPE html>', '<html>', '<head>');
        self::htmlStyles();
        self::htmlScripts();
        self::echoHtml('</head>', '<body>');
        if ( !empty($_SERVER['REQUEST_URI']) ) {
            self::echoHtml('URI: <b>' . $_SERVER['REQUEST_URI'] . '</b><br>');
        }
        self::echoHtml('CM: <b>' . Dragon::$controller . '::' . Dragon::$method . '</b><br>');
        self::echoHtml('Time: <b>' . date('Y-m-d H:i:s', $time) . substr($time, strpos($time, '.')) . '</b><br>');
        
        //tabs switches
        $tabs = [];
        $class = 'active';
        foreach ( array_keys(Debug::$tables) AS $key ) {
            $tabs[] = '<li class="' . $class . '" data-tab="' . $key . '">' . $key . ' (' . count(Debug::$tables[$key]) . ')</li>';
            $class = '';
        }
        self::echoHtml('<br><ul>' . implode('', $tabs) . '</ul>');
        
        self::htmlTables();
        self::echoHtml('</body>', '</html>');
        
        $html = ob_get_clean();

        $filename = $time . '.html';
        file_put_contents($path . $filename, $html);
        file_put_contents($path . 'last.html', $html);
        
        Debug::$tables = [];
    }
    
    /**
     * @param array $files
     */
    private static function history($files)
    {
        foreach ( $files AS $file ) {
            $data = file_get_contents($file);
            preg_match("/URI: <b>([^<]+)/", $data, $match);
            preg_match("/(\d+\.\d+)\.html/", $file, $time);
            
            Debug::$tables[__FUNCTION__][] = [
                'URI' => $match[1],
                'date' => date('Y-m-d H:i:s', $time[1]) . substr($time[1], strpos($time[1], '.')),
                '' => '<a href="' . Dragon::$host . 'tmp/debug/' . $time[1] . '.html">view</a>'
            ];
        }
    }
    
    private static function echoHtml()
    {
        echo implode(PHP_EOL, func_get_args()), PHP_EOL;
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
            table tr td table { display: table; }
            table th { border-bottom: 2px solid black; }
            table tbody tr:nth-child(even) { background: #eee; }
            table tfoot tr td { border: 0; margin-top: 4px; color: gray; }
            .history { margin-top: 50px; }
            .history a { display: block; }
            .collapsable { cursor: pointer; }
            .collapsable + * { display: none; padding-left: 10px; margin-top: 10px; }
            .red { color: red; }
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
                
                $("table .collapsable").on("click", function() {
                    $(this).next().slideToggle();
                });
            });
        </script>', PHP_EOL;
    }
    
    private static function htmlTables()
    {
        //tabs with tables
        $class = 'active';
        foreach ( Debug::$tables AS $key => $table ) {
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

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
        if ( !defined('DRAGON_DEBUG') || !DRAGON_DEBUG ) {
            return;
        }
        
        self::updateHistory();

        $time = microtime(true);
        
        //ob_start();
        $html =
            self::echoHtml('<!DOCTYPE html>', '<html>', '<head>')
            . self::echoHtml('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">')
            . self::htmlStyles()
            . self::htmlScripts()
            . self::echoHtml('</head>', '<body>');

        if ( !empty($_SERVER['REQUEST_URI']) ) {
            $html .= self::echoHtml('URI: <b>' . $_SERVER['REQUEST_URI'] . '</b><br>');
        }
        if (Dragon::$controller instanceof \controllers\IController)
            $html .= self::echoHtml('CM: <b>' . get_class(Dragon::$controller) . '->' . Dragon::$method . '</b><br>');
        $html .= self::echoHtml('Time: <b>' . date('Y-m-d H:i:s', $time) . substr($time, strpos($time, '.')) . '</b><br>');
        
        $last = \core\Router::gi()->getHost() . 'tmp/debug/last.html';
        $html .= self::echoHtml('Last: <a href="' . $last . '">' . $last . '</a><br>');
        
        //tabs switches
        $tabs = [];
        $class = 'active';
        foreach ( array_keys(Debug::$tables) AS $key ) {
            $tabs[] = '<li class="' . $class . '" data-tab="' . $key . '">' . $key . ' (' . count(Debug::$tables[$key]) . ')</li>';
            $class = '';
        }
        $html .= self::echoHtml('<br><ul>' . implode('', $tabs) . '</ul>');
        
        $html .= self::htmlTables();
        $html .= self::echoHtml('</body>', '</html>');
        
        //$html = ob_get_clean();

        $filename = $time . '.html';
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . $filename, $html);
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . 'last.html', $html);
        
        Debug::$tables = [];
    }

    private static function updateHistory()
    {
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
    }
    
    /**
     * @param array $files
     */
    private static function history($files)
    {
        Debug::$tables[__FUNCTION__] = [];

        foreach ( $files AS $file ) {
            if (strpos($file, 'last.html') > 0)
                continue;

            $data = file_get_contents($file);
            preg_match("/URI: <b>([^<]+)/", $data, $match);
            preg_match("/(\d+\.\d+)\.html/", $file, $time);
            
            Debug::$tables[__FUNCTION__][] = [
                'URI' => $match[1],
                'date' => date('Y-m-d H:i:s', $time[1]) . substr($time[1], strpos($time[1], '.')),
                '' => '<a href="' . \core\Router::gi()->getHost() . 'tmp/debug/' . $time[1] . '.html" target="_blank">view</a>'
            ];
        }
    }
    
    private static function echoHtml(): string
    {
        return implode(PHP_EOL, func_get_args()) . PHP_EOL;
    }
    
    private static function htmlStyles(): string
    {
        return '<style type="text/css">
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
        </style>' . PHP_EOL;
    }
    
    private static function htmlScripts(): string
    {
        return '<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
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
        </script>' . PHP_EOL;
    }
    
    private static function htmlTables(string $class = 'active'): string
    {
        $output = '';

        //tabs with tables
        foreach ( Debug::$tables AS $key => $table ) {
            if (empty($table))
                continue;

            $output .= '<table class="' . $class . '" id="' . $key . '" cellspacing="0">';
            $class = '';
            
            //thead - if first table row keys is not numeric
            if ( !is_numeric(key($table[0])) ) {
                $output .= '<thead><tr>';
                $output .= '<th>N.</th>';
                foreach ( array_keys($table[0]) AS $columnKey ) {
                    $output .= '<th>' . $columnKey . '</th>';
                }
                $output .= '</tr></thead>';
            }
            
            $doFooter = count($table) > 1;
            if ( $doFooter ) {
                $footer = [];
            }
            
            //tbody rows
            $i = 1;
            $output .= '<tbody>';
            foreach ( $table AS $row ) {
                $output .= '<tr>';
                $output .= '<td>' . $i . '</td>';
                foreach ( $row AS $cellKey => $cell ) {
                    $output .= '<td>' . $cell . '</td>';
                    
                    if ( $doFooter ) {
                        $footer[$cellKey][] = $cell;
                    }
                }
                $output .= '</tr>';
                $i++;
            }
            $output .= '</tbody>';
            
            //footer
            if ( $doFooter ) {
                $output .= '<tfoot><tr>';
                $output .= '<td></td>';
                foreach ( $footer AS $vals ) {
                    $output .= '<td>';
                    $tmp = array_filter($vals, 'is_numeric');
                    if ( count($tmp) == count($vals) ) {
                        $output .= array_sum($vals);
                    }
                    $output .= '</td>';
                }
                $output .= '</tr></tfoot>';
            }
            
            $output .= '</table>' . PHP_EOL;
        }

        return $output;
    }
    
    /**
     * Generate debug attachable to site
     * @return string
     */
    public static function onsite(): string
    {
        self::updateHistory();

        $html = '';

        $html .= '<style type="text/css">
        #dragon-debug { position: absolute; top: 0; left: 50%; z-index: 1000; background-color: white; height: auto; max-height: 50%; max-width: 50%; border: 1px solid black; overflow: auto; resize: both; min-width: 300px; }
        
        #dragon-debug > span { padding: 0 5px; cursor: pointer; }
        #dragon-debug > span:hover { background-color: silver; }
        #dragon-debug > b { padding-right: 10px; }
        #dragon-debug-handle { background-color: silver; cursor: move; margin-right: 5px; }

        #dragon-debug > table { display: none; }
        #dragon-debug > table.active { display: block; }
        #dragon-debug > table tr > * { border-bottom: 1px solid silver; border-left: 1px solid silver; } 
        #dragon-debug > table tr *:first-child { border-left: 0; } 
        #dragon-debug > table tr td {border-bottom: 1px solid silver; padding: 4px 6px; }
        #dragon-debug > table tbody tr:nth-child(even) { background: #eee; }
        #dragon-debug > table th { border-bottom: 2px solid black; white-space: nowrap; }
        #dragon-debug > table tfoot tr td { border: 0; margin-top: 4px; color: gray; }

        #dragon-debug > table td div.collapsable { cursor: pointer; }
        #dragon-debug > table td div.collapsable + div { display: none; }
        #dragon-debug > table td div.collapsable + div.active { display: block; }
        </style>';
        
        $html .= '<div id="dragon-debug">';
        $html .= '<span id="dragon-debug-handle">=</span><span id="dragon-debug-close">X</span>';
        $html .= '<b>' . str_replace("controllers\\", '', get_class(Dragon::$controller)) . '->' . Dragon::$method . '</b>';

        foreach (Debug::$tables as $key => $values) {
            $html .= '<span onclick=" dragonDebug.showTable(\'' . $key . '\'); ">' . $key . ' (' . count($values) . ')</span>';
        }

        $html .= self::htmlTables('');

        $html .= '</div>';

        $html .= '<script type="application/javascript">
        dragonDebug = {
            showTable: function (key) {
                let current = document.getElementById(key);
                for (e of document.getElementById("dragon-debug").getElementsByClassName("active")) {
                    if (e != current)
                        e.className = "";
                }
                document.getElementById(key).className = current.className == "active" ? "" : "active";
            },
            moving: false,
            offset: [0, 0]
        };

        for (e of document.getElementById("dragon-debug").getElementsByClassName("collapsable")) {
            e.onclick = function () {
                this.nextSibling.className = this.nextSibling.className == "active" ? "" : "active";
            }
        }
        
        document.getElementById("dragon-debug-close").onclick = function () {
            document.getElementById("dragon-debug").remove();
        }

        document.getElementById("dragon-debug-handle").addEventListener("mousedown", function (e) {
            dragonDebug.moving = true;
            dragonDebug.offset = [this.parentElement.offsetLeft - e.clientX, this.parentElement.offsetTop - e.clientY];
        }, true);
        document.addEventListener("mousemove", function (e) {
            if (dragonDebug.moving) {
                e.preventDefault();
                document.getElementById("dragon-debug").style.left = e.clientX + dragonDebug.offset[0] + "px";
                document.getElementById("dragon-debug").style.top = e.clientY + dragonDebug.offset[1] + "px";
            }
        }, true);
        document.addEventListener("mouseup", function (e) {
            dragonDebug.moving = false;
        }, true);
        
        </script>';

        return $html;
    }

}

<?php

namespace core;

use \Exception;

/**
 * Debug
 * Developer tools
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Debug
{
    /**
     * @var Debug
     */
    private static $instance;

    /**
     * @var array
     */
    private static $tables = [];
    /**
     * @var array
     */
    private static $timers = [];

    /**
     * @var int
     */
    private static $initialized = 0;

    /**
     * Initialize
     * @return bool
     */
    private static function init(): bool
    {
        if (self::$initialized == 0) {
            if (!defined('DRAGON_DEBUG'))
                return false;

            self::$initialized = DRAGON_DEBUG ? 1 : 2;
        }

        return self::$initialized == 2;
    }

    /**
     * Dump data
     * @param mixed ...$args
     */
    public static function var_dump(...$args)
    {
        if (self::init())
            return;

        if (!empty($args)) {
            foreach ($args as $one) {
                ob_start();
                var_dump($one);
                $content = ob_get_clean();

                self::$tables[__FUNCTION__][] = ['dump' => '<div class="collapsable">' . $content . '</div>' . '<div>' . self::backtrace() . '</div>'];
            }
        }
    }

    /**
     * List of loaded files
     * @param string $file
     */
    public static function files(string $file)
    {
        if (self::init())
            return;

        $exists = file_exists($file);
        $str = '<div class="collapsable ' . ($exists ? '' : 'red') . '">' . $file . '</div>' . '<div>' . self::backtrace() . '</div>';

        foreach ((self::$tables[__FUNCTION__] ?? []) as $i => $row) {
            if ($row['file'] == $str) {
                self::$tables[__FUNCTION__][$i]['hits'] += 1;
                return;
            }
        }

        self::$tables[__FUNCTION__][] = [
            'file' => $str,
            'size (bytes)' => $exists ? filesize($file) : 0,
            'hits' => 1
        ];
    }

    /**
     * Measure time
     * @param string $key
     */
    public static function timer(string $key)
    {
        if (self::init())
            return;

        if (!isset(self::$timers[$key])) {
            self::$timers[$key] = microtime(true);
        } else {
            self::$tables[__FUNCTION__][] = [
                'key' => '<div class="collapsable">' . $key . '</div>' . '<div>' . self::backtrace() . '</div>',
                'time (msec)' => sprintf('%f', (microtime(true) - self::$timers[$key]) * 1000)
            ];
            unset(self::$timers[$key]);
        }
    }

    /**
     * Database queries
     * @param string $query
     * @param array $hidden
     * @param array $otherColumns
     */
    public static function query(string $query, array $hidden = [], array $otherColumns = [])
    {
        if (self::init())
            return;

        $query = '<div class="collapsable">' . $query . '</div>';

        if (!empty($hidden)) {
            $html = '<div><table cellspacing="0">';

            if (is_array(reset($hidden))) {
                $html .= '<thead><tr>';
                foreach (array_keys(reset($hidden)) as $key) {
                    $html .= '<th>' . $key . '</th>';
                }
                $html .= '</tr></thead>';
            }

            $html .= '<tbody>';
            foreach ($hidden as $row) {
                $html .= '<tr>';
                if (is_array($row)) {
                    foreach ($row as $value) {
                        $html .= '<td>' . var_export($value, true) . '</td>';
                    }
                } else {
                    $html .= '<td>' . var_export($row, true) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            $query .= $html;
        }

        self::$tables[__FUNCTION__][] = array_merge(['query' => $query], $otherColumns);
    }

    /**
     * Format Exception backtrace for print
     * @return string
     */
    private static function backtrace(): string
    {
        $values = [];
        foreach (debug_backtrace(2) as $i => $entry) {
            $line = '#' . $i . ' ';
            if (array_key_exists('file', $entry))
                $line .= $entry['file'] . '(' . $entry['line'] . '): ';
            if (array_key_exists('class', $entry))
                $line .= $entry['class'] . $entry['type'];
            $line .= $entry['function'];
            $values[] = $line;
        }
        return '<pre>' . implode('<br>', $values) . '</pre>';
    }


    /**
     * Generate debug html file from collected data
     */
    public static function generate()
    {
        self::updateHistory();
        foreach (array_keys(self::$timers) as $key)
            self::timer($key);

        $time = microtime(true);

        $counts = [];
        foreach (self::$tables as $key => $table)
            $counts[$key] = count($table);

        $html = (new View('/views/elements/debug/report', [
            'uri' => IS_CLI ? $GLOBALS['_SERVER']['SCRIPT_NAME'] : ($_SERVER['REQUEST_URI'] ?? ''),
            'cm' => Dragon::$controller instanceof \controllers\IController ? get_class(Dragon::$controller) . '->' . Dragon::$method : '',
            'time' => date('Y-m-d H:i:s', $time) . substr($time, strpos($time, '.')),
            'last' => Router::gi()->getHost() . 'tmp/debug/last.html',
            'tabs' => array_keys(self::$tables),
            'counts' => $counts,
            'tables' => self::htmlTables()
        ]))->render();

        $filename = $time . '.html';
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . $filename, $html);
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . 'last.html', $html);

        self::$tables = [];
    }

    /**
     * Clean up tmp debug files
     */
    private static function updateHistory()
    {
        $path = BASE_PATH . DS . 'tmp' . DS . 'debug' . DS;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        //clear old files
        if (file_exists($path . 'last.html')) {
            unlink($path . 'last.html');
        }

        $files = glob($path . '*.html');
        if (count($files) >= 10) {
            rsort($files);
            for ($i = count($files) - 1; $i >= 10; $i--) {
                unlink($files[$i]);
                unset($files[$i]);
            }
        }

        self::history($files);
    }

    /**
     * @param array $files
     */
    private static function history(array $files)
    {
        self::$tables[__FUNCTION__] = [];

        foreach ($files as $file) {
            if (strpos($file, 'last.html') > 0)
                continue;

            $data = file_get_contents($file);
            preg_match("/URI: <b>([^<]*)/", $data, $match);
            preg_match("/(\d+\.\d+)\.html/", $file, $time);

            self::$tables[__FUNCTION__][] = [
                'URI' => $match[1],
                'date' => date('Y-m-d H:i:s', $time[1]) . substr($time[1], strpos($time[1], '.')),
                '' => '<a href="' . Router::gi()->getHost() . 'tmp/debug/' . $time[1] . '.html" target="_blank">view</a>'
            ];
        }
    }

    /**
     * Generate HTML tables
     * @param string $class
     * @return string
     */
    private static function htmlTables(string $class = 'active'): string
    {
        $output = '';

        //tabs with tables
        foreach (self::$tables as $key => $table) {
            if (empty($table))
                continue;

            $output .= '<table class="' . $class . '" id="' . $key . '" cellspacing="0">';
            $class = '';

            //thead - if first table row keys is not numeric
            if (!is_numeric(key($table[0]))) {
                $output .= '<thead><tr>';
                $output .= '<th>N.</th>';
                foreach (array_keys($table[0]) as $columnKey) {
                    $output .= '<th>' . $columnKey . '</th>';
                }
                $output .= '</tr></thead>';
            }

            $doFooter = count($table) > 1;
            if ($doFooter) {
                $footer = [];
            }

            //tbody rows
            $i = 1;
            $output .= '<tbody>';
            foreach ($table as $row) {
                $output .= '<tr>';
                $output .= '<td>' . $i . '</td>';
                foreach ($row as $cellKey => $cell) {
                    $output .= '<td>' . $cell . '</td>';

                    if ($doFooter) {
                        $footer[$cellKey][] = $cell;
                    }
                }
                $output .= '</tr>';
                $i++;
            }
            $output .= '</tbody>';

            //footer
            if ($doFooter) {
                $output .= '<tfoot><tr>';
                $output .= '<td></td>';
                foreach ($footer as $vals) {
                    $output .= '<td>';
                    $tmp = array_filter($vals, 'is_numeric');
                    if (count($tmp) == count($vals)) {
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
        foreach (array_keys(self::$timers) as $key)
            self::timer($key);

        $counts = [];
        foreach (self::$tables as $key => $table)
            $counts[$key] = count($table);

        return (new View('/views/elements/debug/onsite', [
            'cm' => str_replace("controllers\\", '', get_class(Dragon::$controller)) . '->' . Dragon::$method,
            'tabs' => array_keys(self::$tables),
            'counts' => $counts,
            'tables' => self::htmlTables('')
        ]))->render();
    }

}

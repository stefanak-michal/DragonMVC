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
    private $tables = [];
    /**
     * @var array
     */
    private $timers = [];

    /**
     * Singleton
     * @return Debug
     */
    public static function gi(): Debug
    {
        if (empty(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Dump data
     * @param mixed ...$args
     */
    public static function var_dump(...$args)
    {
        if (defined('DRAGON_DEBUG') && !DRAGON_DEBUG)
            return;

        if (!empty($args)) {
            foreach ($args as $one) {
                ob_start();
                var_dump($one);
                $content = ob_get_clean();

                self::gi()->tables[__FUNCTION__][] = ['dump' => '<div class="collapsable">' . $content . '</div>' . '<div>' . self::backtrace() . '</div>'];
            }
        }
    }

    /**
     * List of loaded files
     * @param string $file
     */
    public static function files(string $file)
    {
        if (defined('DRAGON_DEBUG') && !DRAGON_DEBUG)
            return;

        $exists = file_exists($file);
        self::gi()->tables[__FUNCTION__][] = [
            'file' => '<div class="collapsable ' . ($exists ? '' : 'red') . '">' . $file . '</div>' . '<div>' . self::backtrace() . '</div>',
            'size (bytes)' => $exists ? filesize($file) : 0
        ];
    }

    /**
     * Measure time
     * @param string $key
     */
    public static function timer(string $key)
    {
        if (defined('DRAGON_DEBUG') && !DRAGON_DEBUG)
            return;

        if (!isset(self::gi()->timers[$key])) {
            self::gi()->timers[$key] = microtime(true);
        } else {
            self::gi()->tables[__FUNCTION__][] = [
                'key' => '<div class="collapsable">' . $key . '</div>' . '<div>' . self::backtrace() . '</div>',
                'time (msec)' => sprintf('%f', (microtime(true) - self::gi()->timers[$key]) * 1000)
            ];
            unset(self::gi()->timers[$key]);
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
        if (defined('DRAGON_DEBUG') && !DRAGON_DEBUG) {
            return;
        }

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

        self::gi()->tables[__FUNCTION__][] = array_merge(['query' => $query], $otherColumns);
    }

    /**
     * Format Exception backtrace for print
     * @return string
     */
    private static function backtrace(): string
    {
        $backtrace = (new Exception)->getTraceAsString();
        $backtrace = preg_split("/[\r\n]+/", strip_tags($backtrace));
        $backtrace = array_slice($backtrace, 2);
        for ($i = 0; $i < count($backtrace); $i++) {
            $backtrace[$i] = preg_replace('/^#\d+/', '#' . $i, $backtrace[$i]);
        }
        return implode('<br>', $backtrace);
    }


    /**
     * Generate debug html file from collected data
     */
    private function generate()
    {
        if (defined('DRAGON_DEBUG') && !DRAGON_DEBUG) {
            return;
        }

        $this->updateHistory();
        foreach (array_keys($this->timers) as $key)
            self::timer($key);

        $time = microtime(true);

        $counts = [];
        foreach ($this->tables as $key => $table)
            $counts[$key] = count($table);

        $html = (new View('/views/elements/debug/report', [
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'cm' => Dragon::$controller instanceof \controllers\IController ? get_class(Dragon::$controller) . '->' . Dragon::$method : null,
            'time' => date('Y-m-d H:i:s', $time) . substr($time, strpos($time, '.')),
            'last' => Router::gi()->getHost() . 'tmp/debug/last.html',
            'tabs' => array_keys($this->tables),
            'counts' => $counts,
            'tables' => $this->htmlTables()
        ]))->render();

        $filename = $time . '.html';
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . $filename, $html);
        file_put_contents(BASE_PATH . DS . 'tmp' . DS . 'debug' . DS . 'last.html', $html);

        $this->tables = [];
    }

    private function updateHistory()
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

        $this->history($files);
    }

    /**
     * @param array $files
     */
    private function history(array $files)
    {
        $this->tables[__FUNCTION__] = [];

        foreach ($files as $file) {
            if (strpos($file, 'last.html') > 0)
                continue;

            $data = file_get_contents($file);
            preg_match("/URI: <b>([^<]*)/", $data, $match);
            preg_match("/(\d+\.\d+)\.html/", $file, $time);

            $this->tables[__FUNCTION__][] = [
                'URI' => $match[1],
                'date' => date('Y-m-d H:i:s', $time[1]) . substr($time[1], strpos($time[1], '.')),
                '' => '<a href="' . Router::gi()->getHost() . 'tmp/debug/' . $time[1] . '.html" target="_blank">view</a>'
            ];
        }
    }

    private function htmlTables(string $class = 'active'): string
    {
        $output = '';

        //tabs with tables
        foreach ($this->tables as $key => $table) {
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
        self::gi()->updateHistory();
        foreach (array_keys(self::gi()->timers) as $key)
            self::timer($key);

        $counts = [];
        foreach (self::gi()->tables as $key => $table)
            $counts[$key] = count($table);

        return (new View('/views/elements/debug/onsite', [
            'cm' => str_replace("controllers\\", '', get_class(Dragon::$controller)) . '->' . Dragon::$method,
            'tabs' => array_keys(self::gi()->tables),
            'counts' => $counts,
            'tables' => self::gi()->htmlTables('')
        ]))->render();
    }

    public function __destruct()
    {
        $this->generate();
    }

}

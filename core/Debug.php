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
        if (!defined('DRAGON_DEBUG') || !DRAGON_DEBUG) {
            return;
        }

        $args = func_get_args();

        if (!empty($args)) {
            foreach ($args as $one) {
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
        if (!defined('DRAGON_DEBUG') || !DRAGON_DEBUG) {
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
        if (!defined('DRAGON_DEBUG') || !DRAGON_DEBUG) {
            return;
        }

        if (!isset(self::$timers[$key])) {
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
     * @param string $query
     * @param array $hidden
     * @param array $otherColumns
     */
    public static function query(string $query, array $hidden = [], array $otherColumns = [])
    {
        if (!defined('DRAGON_DEBUG') || !DRAGON_DEBUG) {
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

        self::$tables[__FUNCTION__][] = array_merge(['query' => $query], $otherColumns);
    }
}

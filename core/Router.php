<?php

namespace core;

/**
 * Router
 * Work with URI
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Router
{

    /**
     * Base for all URI
     *
     * @var string
     */
    private $project_host;

    /**
     * Definition of allowed routes from config file
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var Router
     */
    private static $instance;

    /**
     * Singleton
     *
     * @return Router
     */
    public static function gi(): Router
    {
        if (self::$instance == null)
            self::$instance = new Router();

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadRoutes();

        $this->project_host = Config::gi()->get('project_host');
        if (empty($this->project_host) && isset($_SERVER['SERVER_PORT'], $_SERVER['HTTP_HOST'])) {
            $this->project_host = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        }
        if (empty($this->project_host) && IS_CLI) {
            $this->project_host = 'http://' . php_uname('n');
        }
        if (empty($this->project_host)) {
            trigger_error('Not specified project host', E_USER_WARNING);
        }
        $this->project_host = rtrim($this->project_host, '/') . '/';
        Config::gi()->set('project_host', $this->project_host);
    }

    /**
     * Load routes from config file and clean up grouping
     */
    private function loadRoutes()
    {
        foreach (Config::gi()->get('routes', []) as $key => $value) {
            if (is_array($value)) {
                $key = str_replace('\\', '/', $key);
                if (strpos($key, 'controllers') === false)
                    $key = 'controllers/' . $key;

                foreach ($value as $mask => $route) {
                    if (is_numeric($mask))
                        $this->routes[] = $key . '/' . $route;
                    else
                        $this->routes[$mask] = $key . '/' . $route;
                }
            } else {
                if (strpos($value, 'controllers') === false)
                    $value = 'controllers/' . $value;

                if (is_numeric($key))
                    $this->routes[] = $value;
                else
                    $this->routes[$key] = $value;
            }
        }
    }

    /**
     * Get project host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->project_host;
    }

    /**
     * Switch to generate secured URI (https)
     *
     * @param bool $secure
     */
    public function setSecureHost(bool $secure = true)
    {
        if ($secure) {
            if (strpos($this->project_host, 'https') === false) {
                $this->project_host = str_replace('http', 'https', $this->project_host);
            }
        } else {
            if (strpos($this->project_host, 'https') !== false) {
                $this->project_host = str_replace('https', 'http', $this->project_host);
            }
        }
    }

    /**
     * Generate homepage URI
     *
     * @param array $query
     * @return string
     */
    public function homepage(array $query = array()): string
    {
        $uri = $this->project_host;

        if (is_array($query) && !empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    /**
     * Generate URI
     *
     * @param string $controller
     * @param string $method
     * @param array $vars
     * @param array $query
     * @return string
     */
    public function url(string $controller, string $method = 'index', array $vars = [], array $query = []): string
    {
        if (empty($controller) || empty($method))
            exit;

        $uri = '';
        $controller = str_replace('\\', '/', $controller);
        $masks = array_filter($this->routes, function ($value) use ($controller, $method) {
            $value = strtolower($value);
            return $value == strtolower($controller . '/' . $method) || $value == strtolower('controllers/' . $controller . '/' . $method);
        });

        if (empty($masks))
            trigger_error('No defined route for ' . $controller . '/' . $method, E_USER_WARNING);

        foreach (array_keys($masks) as $mask) {
            if (is_integer($mask))
                continue;

            //check number of defined variables against mask
            if (count($vars) != preg_match_all("/%[dis]/", $mask))
                continue;

            $this->replaceMaskVariables($mask, $vars);
            $uri = $this->project_host . $mask;
            break;
        }

        if (empty($uri)) {
            $uri = $this->project_host . $controller . '/' . $method;
            if (!empty($vars)) {
                $uri .= '/' . implode('/', array_map(function ($value) {
                        return filter_var($value, FILTER_SANITIZE_ENCODED);
                    }, $vars));
            }
        }

        if (!empty($query))
            $uri .= '?' . http_build_query($query);

        return $uri;
    }

    /**
     * @param string $mask
     * @param array $vars
     */
    private function replaceMaskVariables(string &$mask, array $vars)
    {
        if (empty($vars))
            return;

        $i = 0;
        while (preg_match("/%[dis]/", $mask, $match)) {
            switch ($match[0]) {
                case '%d':
                    $mask = preg_replace("/%d/", (string)floatval($vars[$i]), $mask, 1);
                    break;

                case '%i':
                    $mask = preg_replace("/%i/", (string)intval($vars[$i]), $mask, 1);
                    break;

                case '%s':
                    $mask = preg_replace("/%s/", filter_var($vars[$i], FILTER_SANITIZE_ENCODED), $mask, 1);
                    break;
            }

            $i++;
        }
    }

    /**
     * Get actual URI
     *
     * @param bool $getParams
     * @return string
     */
    public function current(bool $getParams = false): string
    {
        $uri = 'http';
        if ($_SERVER['SERVER_PORT'] != 80) {
            $uri .= 's';
        }

        $uri .= '://';
        $uri .= $_SERVER['SERVER_NAME'];

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $uri .= ':' . $_SERVER['SERVER_PORT'];
        }

        $uri .= $_SERVER['REQUEST_URI'];

        if (!$getParams and strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return $uri;
    }

    /**
     * Redirect
     *
     * @param string $uri
     * @param string $message
     * @param int $code
     */
    public function redirect(string $uri, string $message = '', int $code = 302)
    {
        if (!empty($uri)) {
            if (!empty($message)) {
                setcookie('message', $message, time() + 60, '/');
            }

            if (DRAGON_DEBUG) {
                header('Content-Type: text/html');
                echo (new View('elements/debug/backtrace', [
                    'bt' => debug_backtrace(),
                    'url' => $uri,
                    'code' => $code,
                    'message' => $message
                ]))->render();
            } else {
                header('Location: ' . $uri, true, $code);
            }

            exit;
        }
    }

    /**
     * Find route
     *
     * @param string $path
     * @return array
     */
    public function findRoute(string $path): array
    {
        $output = array();

        foreach ($this->routes as $mask => $route) {
            $output = $this->match($path, $mask, $route);
            if (!empty($output))
                break;
        }

        return $output;
    }

    /**
     * Match specific route
     *
     * @param string $path
     * @param string|int $mask
     * @param string $route
     * @return array
     */
    private function match(string $path, $mask, string $route): array
    {
        $output = [];

        if (!is_integer($mask))
            $mask = str_replace(['%i', '%s', '%d'], ['(-?\d+)', '([\w\-%]+)', '(-?[\d\.]+)'], $mask);

        $pattern = "/^";
        $pattern .= str_replace('/', '\/', is_integer($mask) ? ($route . '((?=/)(.*))?') : $mask);
        $pattern .= "$/i";

        if (preg_match($pattern, $path, $vars)) {
            $uri = preg_split("/[\/\\\]/", $route, -1, PREG_SPLIT_NO_EMPTY);
            $output = [
                'method' => array_pop($uri),
                'controller' => $uri,
                'vars' => []
            ];

            if (is_integer($mask)) {
                if (!empty($vars[1])) {
                    $output['vars'] = preg_split("/[\\/]/", $vars[1], -1, PREG_SPLIT_NO_EMPTY);
                }
            } else {
                array_shift($vars);
                $output['vars'] = array_values($vars);
            }
        }

        return $output;
    }

}

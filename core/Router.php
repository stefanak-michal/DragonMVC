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
     * @var array
     */
    private $masksCache = [];

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
        $this->parseControllerRoutes();
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
                if (strpos($key, 'controllers') !== 0)
                    $key = 'controllers/' . $key;

                foreach ($value as $mask => $route)
                    $this->routes[$mask] = $key . '/' . $route;
            } else {
                $value = str_replace('\\', '/', $value);
                if (strpos($value, 'controllers') !== 0)
                    $value = 'controllers/' . $value;

                $this->routes[$key] = $value;
            }
        }
    }

    /**
     * Load routes from controllers files as @route annotation for public methods
     */
    private function parseControllerRoutes()
    {
        if (!Config::gi()->get('parseControllerRoutes', false))
            return;

        $dir = new \RecursiveDirectoryIterator(BASE_PATH . DS . 'controllers');
        $iterator = new \RecursiveIteratorIterator($dir);
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        foreach($regex as $file) {
            $file = $file[0];

            if (strpos(file_get_contents($file), '@route') === false)
                continue;

            $ns = null;
            $routes = [];
            $cls = null;
            foreach (file($file) as $line) {
                if (strpos($line, 'namespace') !== false) {
                    $ns = trim(str_replace('namespace', '', $line), " ;\r\n") . '/';
                    $ns = str_replace("\\", "/", $ns);
                    continue;
                }

                $match = [];
                if (!empty($ns) && strpos($line, 'class') !== false && preg_match("/class\s+(\w+)/", $line, $match)) {
                    $cls = $ns . trim($match[1]);
                    continue;
                }

                if (strpos($line, '@route') !== false) {
                    $parts = explode(' ', $line);
                    $parts = array_values(array_filter($parts));
                    $routes[] = trim($parts[2]);
                    continue;
                }

                $match = [];
                if (!empty($routes) && !empty($cls) && preg_match("/public function (\w+)/", $line, $match)) {
                    foreach ($routes as $route)
                        $this->routes[$route] = $cls . '/' . $match[1];
                    $routes = [];
                }
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
     * @param string $controller className
     * @param string $method
     * @param array $vars
     * @param array $query
     * @return string
     */
    public function url(string $controller, string $method = 'index', array $vars = [], array $query = []): string
    {
        if (empty($controller) || empty($method) || !class_exists($controller))
            exit;

        $uri = '';
        $controller = str_replace('\\', '/', $controller);
        foreach ($this->getMasks($controller, $method) as $mask) {
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
     * Get cached masks for requested controller/method
     * Method url can be called so many times and caching this improves performance
     * @param string $controller
     * @param string $method
     * @return array
     */
    private function getMasks(string $controller, string $method)
    {
        if (empty($this->masksCache)) {
            foreach ($this->routes as $mask => $value) {
                $this->masksCache[$value][] = $mask;
            }
        }

        return $this->masksCache[$controller . '/' . $method] ?? [];
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
                echo (new View('/views/elements/debug/backtrace', [
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

        $mask = str_replace(['%i', '%s', '%d'], ['(-?\d+)', '(' . \core\Config::gi()->get('routeStringRegex', '[\w\-]+') . ')', '(-?[\d\.]+)'], $mask);

        $pattern = "/^";
        $pattern .= str_replace('/', '\/', $mask);
        $pattern .= "$/i";

        if (preg_match($pattern, $path, $vars)) {
            $uri = array_filter(explode('/', $route));
            array_shift($vars);
            $output = [
                'method' => array_pop($uri),
                'controller' => $uri,
                'vars' => array_values($vars)
            ];
        }

        return $output;
    }

}

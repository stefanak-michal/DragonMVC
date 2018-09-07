<?php

namespace core;

/**
 * Router
 * 
 * Work with URI
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
    private $routes = array();
    
    /**
     * @var Router
     */
    private static $instance;
    
    /**
     * Singleton
     * 
     * @return Router
     */
    public static function gi()
    {
        if (self::$instance == null)
            self::$instance = new Router();
        
        return self::$instance;
    }

    /**
     * Construct
     * 
     * @param Config $config
     */
    public function __construct()
    {
        $this->routes = Config::gi()->get('routes');

        $this->project_host = Config::gi()->get('project_host');
        if ( empty($this->project_host) && isset($_SERVER['SERVER_PORT'], $_SERVER['HTTP_HOST']) ) {
            $this->project_host = ( $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
        }

        if ( empty($this->project_host) ) {
            trigger_error('Not specified project host', E_USER_WARNING);
        }
        $this->project_host = rtrim($this->project_host, '/') . '/';
        Config::gi()->set('project_host', $this->project_host);
    }
    
    /**
     * Get project host
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->project_host;
    }

    /**
     * Switch to generate secured URI (https)
     * 
     * @param boolean $bool
     */
    public function setSecureHost($bool = true)
    {
        if ( $bool ) {
            if ( strpos($this->project_host, 'https') === false ) {
                $this->project_host = str_replace('http', 'https', $this->project_host);
            }
        } else {
            if ( strpos($this->project_host, 'https') !== false ) {
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
    public function getHomepageUrl($query = array())
    {
        $uri = $this->project_host;

        if ( is_array($query) && !empty($query) ) {
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
    public function getUrl($controller, $method, $vars = array(), $query = array())
    {
        $uri = $this->project_host;
        if ( !empty($controller) && !empty($method) ) {
            //find right routes
            $masks = array_keys($this->routes, $controller . '/' . $method);
            if ( empty($masks) ) {
                trigger_error('No defined route for ' . $controller . '/' . $method);
            }

            if ( !empty($vars) && !is_array($vars) ) {
                $vars = array($vars);
            }

            foreach ( $masks AS $mask ) {
                //default action if it's not defined mask
                if ( is_integer($mask) ) {
                    $uri .= $controller . '/' . $method;
                } else {
                    if ( !empty($vars) ) {
                        //check number of defined variables against mask
                        if ( count($vars) != preg_match_all("/%[dis]/", $mask) ) {
                            continue;
                        }

                        //translate variables in mask
                        $i = 0;
                        while ( preg_match("/%[dis]/", $mask, $match) ) {
                            if ( !isset($vars[$i]) ) {
                                break;
                            }

                            switch ( $match[0] ) {
                                case '%d':
                                case '%i':
                                    if ( is_numeric($vars[$i]) ) {
                                        $mask = preg_replace("/%[id]/", $vars[$i], $mask, 1);
                                        unset($vars[$i]);
                                    }
                                    break;

                                case '%s':
                                    $mask = preg_replace("/%s/", $vars[$i], $mask, 1);
                                    unset($vars[$i]);
                                    break;
                            }

                            $i++;
                        }

                        if ( strpos($mask, '%') !== false ) {
                            continue;
                        }
                    }

                    $uri .= $mask;
                }

                //append variables which it's not setted
                if ( !empty($vars) ) {
                    $uri .= '/' . implode('/', $vars);
                }

                //append query url part
                if ( is_array($query) && !empty($query) ) {
                    $uri .= '?' . http_build_query($query);
                }

                break;
            }
        }

        return $uri;
    }

    /**
     * Get actual URI
     * 
     * @param boolean $getParams
     * @return string
     */
    public function current($getParams = false)
    {
        $uri = 'http';
        if ( $_SERVER['SERVER_PORT'] != 80 ) {
            $uri .= 's';
        }

        $uri .= '://';
        $uri .= $_SERVER['SERVER_NAME'];

        if ( !in_array($_SERVER['SERVER_PORT'], array(80, 443)) ) {
            $uri .= ':' . $_SERVER['SERVER_PORT'];
        }

        $uri .= $_SERVER['REQUEST_URI'];

        if ( !$getParams AND strpos($uri, '?') !== false ) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return $uri;
    }

    /**
     * Redirect
     * 
     * @param string $uri
     * @param string $message
     */
    public function redirect($uri, $message = '', $code = 302)
    {
        if ( !empty($uri) ) {
            if ( !empty($message) ) {
                setcookie('message', $message, time() + 60, '/');
            }

            if ( DRAGON_DEBUG ) {
                View::renderElement('debug/backtrace', array(
                    'bt' => debug_backtrace(),
                    'url' => $uri,
                    'code' => $code,
                    'message' => $message
                ));
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
     * @return string
     */
    public function findRoute($path)
    {
        $output = array();
        
        foreach ( $this->routes AS $mask => $route ) {
            if ( is_array($route) ) {
                foreach ( $route AS $controller => $subroute ) {
                    $output = $this->match($path, $controller, trim($mask, '/') . '/' . $subroute);
                    if ( $output !== false ) {
                        break 2;
                    }
                }
            } else {
                $output = $this->match($path, $mask, $route);
                if ( $output !== false ) {
                    break;
                }
            }
        }
        
        return $output;
    }
    
    /**
     * Match specific route
     * 
     * @param string $path
     * @param string|int $mask
     * @param string $route
     * @return array|boolean
     */
    private function match($path, $mask, $route)
    {
        $output = false;
        
        $res = preg_match("/^" . str_replace('/', '\/', is_integer($mask) ? ($route . '((?=/)(.*))?') : str_replace(array('%i', '%s', '%d'), array('(\d+)', '([\w\-]+)', '([\d\.]+)'), $mask)) . "$/i", $path, $vars);

        if ( $res ) {
            $uri = preg_split("[\\/]", $route, -1, PREG_SPLIT_NO_EMPTY);
            $output = array(
                'method' => array_pop($uri),
                'controller' => $uri,
                'vars' => array()
            );

            if ( is_integer($mask) ) {
                if ( !empty($vars[1]) ) {
                    $output['vars'] = preg_split("[\\/]", $vars[1], -1, PREG_SPLIT_NO_EMPTY);
                }
            } else {
                array_shift($vars);
                $output['vars'] = array_values($vars);
            }
        }
        
        return $output;
    }

}

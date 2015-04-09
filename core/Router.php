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
     * Config
     *
     * @var Config
     */
    private $config;
    
    /**
     * Definition of allowed routes from config file
     *
     * @var array
     */
    private $routes = array();
    
    /**
     * Construct
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->routes = $this->config->get('routes');
        
        $this->project_host = $this->config->get('project_host');
        if ( empty($this->project_host) ) {
            $this->project_host = ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'];
        }
        $this->config->set('project_host', trim($this->project_host, '/') . '/');
    }
    
    /**
     * Switch to generate secured URI (https)
     * 
     * @param boolean $bool
     */
    public function setSecureHost($bool = true)
    {
        if ($bool)
        {
            if (strpos($this->project_host, 'https') === false)
            {
                $this->project_host = str_replace('http', 'https', $this->project_host);
            }
        }
        else
        {
            if (strpos($this->project_host, 'https') !== false)
            {
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
        $uri = $this->current();
        
        if ( ! empty($controller) && ! empty($method) ) {
            $uri = $this->project_host;
            
            //find allowed route
            $mask = array_search($controller . '/' . $method, $this->routes);
            if ( $mask === false ) {
                Dragon::show_error(400, 'No defined route');
            }
            
            if ( is_integer($mask) ) {
                $uri .= $controller . '/' . $method;
            } else {
                $uri .= $mask;
            }
            
            //complete path variables
            if ( ! empty($vars) ) {
                if ( !is_array($vars) ) {
                    $vars = array($vars);
                }
                
                foreach ( $vars AS $var ) {
                    if ( strpos($uri, '%') !== false ) {
                        if ( is_numeric($var) ) {
                            $uri = preg_replace("/%[id]/", $var, $uri);
                        } else {
                            $uri = preg_replace("/%s/", $var, $uri);
                        }
                    } else {
                        $uri .= '/' . $var;
                    }
                }
            }
            
            if ( is_array($query) && !empty($query) ) {
                $uri .= '?' . http_build_query($query);
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
        if ($_SERVER['SERVER_PORT'] != 80)
        {
            $uri .= 's';
        }
        
        $uri .= '://';
        $uri .= $_SERVER['SERVER_NAME'];
        
        if ($_SERVER['SERVER_PORT'] != 80)
        {
            $uri .= ':' . $_SERVER['SERVER_PORT'];
        }
        
        $uri .= $_SERVER['REQUEST_URI'];
        
        if ( ! $getParams AND strpos($uri, '?') !== false)
        {
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
    public function redirect($uri, $message = '')
    {
        if ( ! empty($uri))
        {
            if ( ! empty($message))
            {
                setcookie('message', $message, time() + 60, '/');
            }
            
            header('Location: '. $uri);
            exit;
        }
    }
    
    /**
     * Generate path to asset file
     * 
     * @param string $name
     * @param string $assetType css, js, img
     * @return string
     */
    public function getAssetUrl($name, $assetType)
    {
        $output = '';
        
        if ( ! empty($name) AND ! empty($assetType) ) {
            $files = (array) $this->config->get($assetType);
            if ( array_key_exists($name, $files) ) {
                $output = $this->project_host . 'assets/' . $assetType . '/' . $files[$name]['file'] . '?v=' . $files[$name]['version'];
            }
        }
        
        return $output;
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
            $res = preg_match("/^" . str_replace('/', '\/', 
                        is_integer($mask) ? ($route . '.*') : str_replace(array('%i', '%s', '%d'), array('(\d+)', '([\w\-]+)', '([\d\.]+)'), $mask)
                    ) . "$/", $path, $vars);
            if ( $res ) {
                $uri = preg_split("[\\/]", $route, -1, PREG_SPLIT_NO_EMPTY);
                $output['method'] = array_pop($uri);
                $output['controller'] = $uri;
                array_shift($vars);
                $output['vars'] = array_values($vars);
                break;
            }
        }
        
        return $output;
    }
    
}
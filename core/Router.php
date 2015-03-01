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
     * Construct
     */
    public function __construct($config)
    {
        $this->config = $config;
        
        $this->project_host = $this->config->get('project_host');
        if ( empty($this->project_host) ) {
            $this->project_host = ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . '/';
        }
        $this->config->set('project_host', $this->project_host);
        
        if ( $_SERVER['SERVER_PORT'] == 443 )
        {
            $this->setSecureHost();
        }
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
        
        if ( ! empty($controller) AND ! empty($method))
        {
            $uri = $this->project_host . $controller . '/' . $method . '/';
            
            if ( ! empty($vars)) {
                $uri .= implode('/', $vars);
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
     * Check if exists route
     * 
     * @param array $cmv
     * @return boolean
     */
    public function existsRoute($cmv)
    {
        $routes = $this->config->get('routes');
        $routes = array_map('strtolower', $routes);
        $output = false;
        
        if (is_array($cmv))
        {
            $output = in_array(strtolower($cmv['controller'] . '/' . $cmv['method']), $routes);
        }
        elseif (preg_match("/\w+\/\w+/", $cmv))
        {
            $output = in_array(strtolower($cmv), $routes);
        }
        
        return $output;
    }
    
}
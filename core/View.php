<?php

namespace core;

/**
 * Class View
 * take care of rendering view & layouts
 * it works two ways:
 *  Singleton is used as primary renderer for controller->method calls
 *  Custom instantiation is available for specific view rendering
 * @package core
 */
final class View
{
    /** @var string */
    private $view;
    /** @var string */
    private $layout;
    /** @var array */
    private $vars = [];

    /** @var View */
    private static $instance;

    /**
     * View constructor.
     * @param string $view
     * @param array $vars
     * @param string $layout
     */
    public function __construct($view = null, $vars = [], $layout = null)
    {
        if (!empty($view))
            $this->view($view);
        if (!empty($layout))
            $this->layout($layout);
        $this->vars($vars);
    }

    /**
     * Singleton
     * @return View
     */
    public static function gi()
    {
        if (empty(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Set view file
     * @param string $view
     * @return View
     */
    public function view($view) : View
    {
        $this->view = $this->path($view);
        return $this;
    }

    /**
     * Set layout file
     * @param string $layout
     * @return View
     */
    public function layout($layout) : View
    {
        $this->layout = $this->path($layout);
        return $this;
    }

    /**
     * Set variable
     * @param mixed $key
     * @param mixed $value
     * @return View
     */
    public function set($key, $value) : View
    {
        $this->vars[$key] = $value;
        return $this;
    }

    /**
     * Set variables
     * @param array $vars
     * @return View
     */
    public function vars(array $vars) : View
    {
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    /**
     * Generate
     * @return string
     */
    public function render()
    {
        if (empty($this->view))
            return '';

        Debug::files($this->view);
        
        if (!isset($this->vars['project_host']))
            $this->vars['project_host'] = Router::gi()->getHost();

        ob_start();

        if (!empty($this->vars))
            extract($this->vars);

        include $this->view;

        $content = ob_get_clean();

        //after render clean up memory
        foreach ( $this->vars as $key => $variable )
            unset(${$key});

        if (!empty($this->layout))
            $content = $this->layouted($content);

        return $content;
    }

    /**
     * @param $content
     * @return string
     */
    private function layouted($content)
    {
        Debug::files($this->layout);

        ob_start();

        if (!empty($this->vars))
            extract($this->vars);

        include $this->layout;

        $content = ob_get_clean();

        //after render clean up memory
        foreach ( $this->vars as $key => $variable )
            unset(${$key});

        return $content;
    }

    /**
     * Generate file path and check if exists
     * @param string $str
     * @return string
     */
    private function path($str)
    {
        if (empty($str))
            return null;

        $str = str_replace(array('/', "\\"), DS, $str);
        if (substr($str, 0, 1) == DS)
            $output =  BASE_PATH . $str;
        else
            $output = BASE_PATH . DS . trim(str_replace(array('/', "\\"), DS, Config::gi()->get('viewsDirectory', 'views')), DS) . DS . $str;

        $ext = '.' . ltrim(Config::gi()->get('viewsExtension', 'phtml'), '.');
        if (substr($output, 0, -strlen($ext)) != $ext)
            $output .= $ext;

        if (!file_exists($output)) {
            Debug::var_dump('View file "' . $output . '" not found');
            return null;
        }

        return $output;
    }
}

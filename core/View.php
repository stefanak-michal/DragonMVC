<?php

namespace core;

/**
 * Class View
 * take care of rendering view & layouts
 * it works two ways:
 *  Singleton is used as primary renderer for controller->method calls
 *  Custom instantiation is available for specific view rendering
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class View
{
    /**
     * @var string
     */
    private $view;
    /**
     * @var string
     */
    private $layout;
    /**
     * @var array
     */
    private $vars = [];

    /**
     * @var View
     */
    private static $instance;

    /**
     * View constructor.
     * @param string $view
     * @param array $vars
     * @param string $layout
     */
    public function __construct(string $view = '', array $vars = [], string $layout = '')
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
    public static function gi(): View
    {
        if (empty(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Set view file
     * @param ?string $view
     * @return bool
     */
    public function view(?string $view = null): bool
    {
        $this->view = $this->path((string)$view);
        return !empty($this->view);
    }

    /**
     * Get resolved view filepath
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Set layout file
     * @param ?string $layout
     * @return bool
     */
    public function layout(?string $layout = null): bool
    {
        $this->layout = $this->path((string)$layout);
        return !empty($this->layout);
    }

    /**
     * Set variable
     * @param string $key Variable valid name
     * @param mixed $value
     * @return View
     */
    public function set(string $key, $value) : View
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
    public function render(): string
    {
        if (empty($this->view))
            return !empty($this->layout) ? $this->layouted('') : '';

        Debug::files($this->view);

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
     * @param string $content It's passed to rendered layout
     * @return string
     */
    private function layouted(string $content): string
    {
        Debug::files($this->layout);

        ob_start();

        if (!empty($this->vars))
            extract($this->vars);

        include $this->layout;

        $html = ob_get_clean();

        //after render clean up memory
        foreach ( $this->vars as $key => $variable )
            unset(${$key});

        return $html;
    }

    /**
     * Generate file path and check if exists
     * @param string $str
     * @return string
     */
    private function path(string $str): string
    {
        if (empty($str))
            return '';

        $str = str_replace(array('/', "\\"), DS, $str);
        $ext = '.' . ltrim(Config::gi()->get('viewsExtension', 'phtml'), '.');
        if (substr($str, 0, -strlen($ext)) != $ext)
            $str .= $ext;

        $viewDirectory = trim(str_replace(array('/', "\\"), DS, Config::gi()->get('viewsDirectory', 'views')), DS);

        if (substr($str, 0, 1) == DS)
            $output =  BASE_PATH . $str;
        else
            $output = BASE_PATH . DS . $viewDirectory . DS . $str;

        if (!file_exists($output)) {
            if (substr($str, 0, 1) == DS)
                $output =  dirname(__DIR__) . $str;
            else
                $output = dirname(__DIR__) . DS . $viewDirectory . DS . $str;

            if (!file_exists($output)) {
                \core\Debug::var_dump('File "' . $str . '" not found. It is intentional?');
                $output = '';
            }
        }

        return $output;
    }
}

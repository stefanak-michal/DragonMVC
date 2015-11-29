<?php

namespace core;

/**
 * View
 * 
 * Praca s vystupom/pohladmi
 */
final class View
{

    /**
     * Layout for draw
     *
     * @var string
     */
    private $layout;

    /**
     * View for draw
     *
     * @var string
     */
    private $view;

    /**
     * Config
     *
     * @var Config
     */
    private $config;

    /**
     * Folder with views
     *
     * @var string
     */
    private static $views_dir = 'views';

    /**
     * Default file extension for views
     *
     * @var string
     */
    private static $views_ext = '.phtml';

    /**
     * Variables set up to view
     *
     * @var array
     */
    private $viewVars = array();

    /**
     * Construct
     * 
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Set view to render
     * 
     * @param string $view
     */
    public function setView($view)
    {
        $this->view = self::replaceDirectorySeperator($view);
    }

    /**
     * Set layout to render
     * 
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = self::replaceDirectorySeperator($layout);
    }

    /**
     * Set title of page
     * 
     * @param string $value
     * @param boolean $projectTitle Add prefix with project name
     */
    public function setTitle($value = '', $projectTitle = true)
    {
        $title = $this->config->get('project_title');

        if ( empty($value) ) {
            $value = $title;
        } else {
            if ( $projectTitle ) {
                $value = $value . ' | ' . $title;
            }
        }

        $this->set('title', $value);
    }

    /**
     * Set variable to view
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->viewVars[$key] = $value;
    }

    /**
     * Render view
     */
    public function render()
    {
        if ( !empty($this->view) ) {
            $this->checkExistsView();

            //if we have some layout
            if ( !empty($this->layout) ) {
                ob_start();
            }

            extract($this->viewVars);
            include BASE_PATH . DS . self::$views_dir . DS . $this->view . self::$views_ext;

            //clear memory after render
            foreach ( $this->viewVars as $key => $variable ) {
                unset(${$key});
            }

            //Second part if we have layout
            if ( !empty($this->layout) ) {
                $content = ob_get_clean();

                extract($this->viewVars);
                include BASE_PATH . DS . self::$views_dir . DS . 'layout' . DS . $this->layout . self::$views_ext;

                //again release some memory after render
                foreach ( $this->viewVars as $key => $variable ) {
                    unset(${$key});
                }
            }

            $this->viewVars = array();
        }
    }

    /**
     * Render element
     * 
     * @param string $element Filename of element, optionally with path, without extension
     * @param array $variables
     * @param boolean $return if want return html as string ..default echo
     * @static
     */
    public static function renderElement($element, $variables = array(), $return = false)
    {
        $content = '';

        $elementFile = BASE_PATH . DS . self::$views_dir . DS . 'elements' . DS . self::replaceDirectorySeperator($element) . self::$views_ext;
        if ( file_exists($elementFile) ) {
            if ( $return ) {
                ob_start();
            }

            if ( is_array($variables) AND ! empty($variables) ) {
                extract($variables);
            }

            include $elementFile;

            if ( $return ) {
                $content = ob_get_clean();
            }

            //po vykresleni si trochu vyprazdnime pamat
            foreach ( $variables as $key => $variable ) {
                unset(${$key});
            }
        } else {
            trigger_error('Missing element view file ' . $elementFile);
        }

        return $content;
    }

    /**
     * Check if view exists
     */
    private function checkExistsView()
    {
        $file = BASE_PATH . DS . self::$views_dir . DS . $this->view . self::$views_ext;
        if ( !file_exists($file) ) {
            trigger_error('Missing view file ' . $file);
        }
    }

    /**
     * Pouzije spravny directory separator
     * 
     * @param string $str
     * @return string
     */
    private static function replaceDirectorySeperator($str)
    {
        return str_replace(array('/', "\\"), DS, $str);
    }

}

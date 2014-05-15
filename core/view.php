<?php defined('BASE_PATH') OR exit('No direct script access allowed');

/**
 * View
 * 
 * Praca s vystupom/pohladmi
 */
final class View
{
    /**
     * Instancia objektu
     *
     * @static
     * @var View
     */
    protected static $instance;
    
    /**
     * Layout do ktoreho sa vykresluje
     *
     * @var string
     */
    private $layout;
    /**
     * Pohlad ktory sa ma vykreslit
     *
     * @var string
     */
    private $view;
    /**
     * Priecinok v ktorom su obsiahnute pohlady
     *
     * @var string
     */
    private static $views_dir = 'views';
    /**
     * Default koncovka pohladov
     *
     * @var string
     */
    private static $views_ext = '.phtml';
    /**
     * Premenne posielane do pohladu
     *
     * @var array
     */
    private $viewVars = array();
    
    /**
     * Konstruktor triedy
     */
    public function __construct()
    {
        $this->view = Framework::$controller . DS . Framework::$method;
    }
    
    /**
     * Ziskanie instancie triedy
     * 
     * @return object
     * @static
     */
    public static function gi() 
    {
        if( self::$instance === NULL )
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Nastavenie pohladu na vykreslenie
     * 
     * @param string $view
     */
    public function setView($view)
    {
        $this->view = $view;
        
        $this->checkExistsView();
    }
    
    /**
     * Nastavenie layoutu do ktoreho sa vykresluje
     * 
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }
    
    /**
     * Nastavi title stranky
     * 
     * @param string $value
     * @param boolean $projectTitle Urcuje ci sa ma dat prefix s nazvom projektu
     */
    public function setTitle($value = '', $projectTitle = true)
    {
        $title = Config::gi()->get('project_title');
        
        if (empty($value))
        {
            $value = $title;
        }
        else
        {
            if ($projectTitle)
            {
                $value = $title . ' - ' . $value;
            }
        }
        
        $this->set('title', $value);
    }
    
    /**
     * Nastavenie premennej do pohladu
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->viewVars[$key] = $value;
    }
    
    /**
     * Vykreslenie pohladu
     */
    public function render()
    {
        if ( ! empty($this->view))
        {
            $this->checkExistsView();
            
            //helpers
            if (file_exists(BASE_PATH . DS . 'helpers'))
            {
                $helpers = glob(BASE_PATH . DS . 'helpers' . DS . 'helper*.php');
                if ( ! empty($helpers))
                {
                    foreach ($helpers AS $helper)
                    {
                        require_once ($helper);
                    }
                }
            }
            
            //ak mame aj nejaky layout
            if ( ! empty($this->layout))
            {
                ob_start();
            }

            extract($this->viewVars);
            include_once BASE_PATH . DS . self::$views_dir . DS . $this->view . self::$views_ext;
            
            //po vykresleni si trochu vyprazdnime pamat
            foreach ($this->viewVars as $key => $variable)
            {
                unset(${$key});
            }

            //druha cast ked mame layout
            if ( ! empty($this->layout))
            {
                $content = ob_get_clean();
//                ob_end_clean();

                extract($this->viewVars);
                include_once BASE_PATH . DS . self::$views_dir . DS . 'layout' . DS . $this->layout . self::$views_ext;
                
                //po vykresleni si trochu vyprazdnime pamat
                foreach ($this->viewVars as $key => $variable)
                {
                    unset(${$key});
                }
            }
            
            $this->viewVars = array();
        }
        
    }
    
    /**
     * Vykreslenie elementu
     * 
     * @param string $element Filename of element, optionally with path, without extension
     * @param array $variables
     * @static
     */
    public static function renderElement($element, $variables = array())
    {
        $elementFile = BASE_PATH . DS . self::$views_dir . DS . 'elements' . DS . $element . self::$views_ext;
        if (file_exists($elementFile))
        {
            if (is_array($variables) AND ! empty($variables))
            {
                extract($variables);
            }

            include_once $elementFile;

            //po vykresleni si trochu vyprazdnime pamat
            foreach ($variables as $key => $variable)
            {
                unset(${$key});
            }
        }
    }

    /**
     * Skontroluje existenciu pohladu
     * 
     * @access private
     */
    private function checkExistsView()
    {
        if ( ! file_exists(BASE_PATH . DS . self::$views_dir . DS . $this->view . self::$views_ext))
        {
            Framework::gi()->show_error(404, $this->view);
        }
    }

}
<?php

namespace core;

/**
 * Framework
 * Base class of MVC framework
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Dragon
{

    /**
     * Called controller
     *
     * @static
     * @var \controllers\IController
     */
    public static $controller;

    /**
     * Called method
     *
     * @static
     * @var string
     */
    public static $method;

    /**
     * @static
     * @var array
     */
    public static $vars;

    /**
     * Run a project
     */
    public function run()
    {
        $cmv = [
            'controller' => Config::gi()->get('defaultController'),
            'method' => Config::gi()->get('defaultMethod'),
            'vars' => []
        ];

        if (is_string($cmv['controller']))
            $cmv['controller'] = preg_split("/[\\/]/", $cmv['controller'], -1, PREG_SPLIT_NO_EMPTY);

        $uri = new URI();
        $uri->fetchUriString();
        $this->resolveRoute($cmv, (string)$uri);

        //must be defined before view->render, sorry for hardcode
        if (DRAGON_DEBUG) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);

        if (method_exists(self::$controller, 'beforeMethod')) {
            Debug::timer('beforeMethod');
            self::$controller->beforeMethod();
            Debug::timer('beforeMethod');
        }

        if (method_exists(self::$controller, self::$method)) {
            Debug::timer('Controller logic');
            self::$controller->{self::$method}(...self::$vars);
            Debug::timer('Controller logic');
        }

        if (method_exists(self::$controller, 'afterMethod')) {
            Debug::timer('afterMethod');
            self::$controller->afterMethod();
            Debug::timer('afterMethod');
        }

        \core\debug\Generator::generate();
    }

    /**
     * @param array $cmv
     * @param string $path
     * @return void
     */
    private function resolveRoute(array &$cmv, string $path)
    {
        if (empty($path))
            return;

        $founded = Router::gi()->findRoute($path);
        if (!empty($founded)) {
            $cmv = $founded;
        } else {
            //append uri parts as variables for method invocation
            $cmv['vars'] = explode('/', $path);
        }
    }

    /**
     * @param array $cmv [controller, method, vars]
     */
    private function loadController(array $cmv)
    {
        //if we have nothing to do, then quit
        if (empty($cmv) or empty($cmv['controller']) or empty($cmv['method'])) {
            trigger_error('Unresolved controller->method action', E_USER_ERROR);
            exit;
        }

        if (!is_array($cmv['controller'])) {
            $cmv['controller'] = [$cmv['controller']];
        }

        $this->trySetView($cmv);

        $class = $this->buildControllerName($cmv['controller']);
        self::$method = $cmv['method'];
        self::$vars = $cmv['vars'];
        self::$controller = new $class();
    }

    /**
     * Try to set view file by possible paths
     * @param array $cmv
     */
    private function trySetView(array $cmv)
    {
        $possibleViewFile = [
            implode('/', $cmv['controller']) . '/' . $cmv['method'],
            strtolower(implode('/', $cmv['controller'])) . '/' . $cmv['method'],
            strtolower(implode('/', $cmv['controller']) . '/' . $cmv['method']),
        ];

        $snake_case = [];
        foreach ($cmv['controller'] as $part)
            $snake_case[] = \helpers\Utils::snake_case($part);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . $cmv['method'];
        $possibleViewFile[] = implode('/', $snake_case) . '/' . strtolower($cmv['method']);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . \helpers\Utils::snake_case($cmv['method']);

        foreach ($possibleViewFile as $viewFile) {
            if (View::gi()->view($viewFile))
                break;
        }
    }

    /**
     * @param array $c
     * @return string
     */
    private function buildControllerName(array $c): string
    {
        $last = ucfirst(array_pop($c));
        if (strtolower(reset($c)) != 'controllers')
            array_unshift($c, 'controllers');
        return "\\" . implode("\\", $c) . "\\" . $last;
    }

}

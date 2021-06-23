<?php

namespace controllers;

/**
 * Interface IController
 *
 * @package controllers
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
interface IController
{
    /**
     * Method invoked before called method
     */
    public function beforeMethod();

    /**
     * Method invoked after called method
     */
    public function afterMethod();
}

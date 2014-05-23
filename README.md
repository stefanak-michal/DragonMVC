# Classic MVC PHP Framework

* For small/medium projects
* Deployment - just copy
* Set up
    * In config folder "main.cfg.php"
    * For define CSS/JS config files use assets.cfg.php
    * Defining routes in routes.cfg.php
* Core classes defined as Singleton - get instance method gi()
* Models is for database access methods (ideal usage, one model for one table)
* Available define lookup table in lt.cfg.php (access over Config::lt)

Database class is MeekroDB http://www.meekro.com/
<?php
$aConfig = array(
    //title of project
    'project_title' => 'Dragon MVC',
    'project_email' => '',
    
    //default controller and method
    'defaultController' => '',
    'defaultMethod' => '',

    'mysql' => [
        'user' => '',
        'password' => '',
        'dbName' => '',
        'host' => 'localhost',
        'encoding' => 'utf8'
    ],

);

// Neo4j wrapper default configuration
Neo4j::$auth = \Bolt\helpers\Auth::basic('neo4j', 'neo4j');

if (IS_WORKSPACE) {
    Neo4j::$logHandler = function (string $query, array $params = [], int $executionTime = 0, array $statistics = []) {
        $st = '';
        foreach (array_filter($statistics) as $key => $value) {
            $st .= '<b>' . $key . ':</b> ' . $value . '<br>';
        }

        \core\Debug::query($query, array_slice(debug_backtrace(2), 2), [
            'params' => !empty($params) ? '<pre>' . print_r($params, true) . '</pre>' : '',
            'stats' => !empty($st) ? '<pre>' . $st . '</pre>' : '',
            'time (ms)' => $executionTime
        ]);
    };
}

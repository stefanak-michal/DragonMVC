<?php
/*
 * Routes specification
 * 
 * You can specify path mask by key, if you leave it default integer (array increment), it's used route path
 * allowed variables in mask:
 * %i - integer
 * %d - double (with dot separator)
 * %s - any string
 * 
 * Allowed groups by controller to array
 */
$aConfig = array(
    'routes' => array(
        'homepage/index',
        
        'homepage' => [
            'test' => 'test',
            'v/%s/%s' => 'vars'
        ],
    )
);
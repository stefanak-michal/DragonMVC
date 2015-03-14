<?php
/*
 * Routes specification
 * 
 * You can specify path mask by key, if you leave it default integer (array increment), it's used route path
 * allowed variables in mask:
 * %i - integer
 * %d - double (with dot separator)
 * %s - any string
 */
$aConfig = array(
    'routes' => array(
        'homepage/index',
        'produkt/%i' => 'products/detail',
    )
);
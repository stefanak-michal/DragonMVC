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
        
        
        /*
         * Some examples for better understanding
         */
        
        //with mask and integer argument
        'produkt/%i' => 'products/detail',
        
        //grouped by controller "Products"
        'products' => [
            'index', //method index
            'some-mask/%i' => 'detail', //method detail with mask
        ],
        
        //grouped by controller "Webview" in directory "mobile" .. <project>/controllers/mobile/Webview
        'mobile/webview' => [
            'friends'
        ]
    )
);
<?php
/**
 * Rest API configuration
 */

return [
    'namespace' => 'mtac',

    'endpoints' => [
        // Categories
        \Seeru\Mtac\Endpoints\Category\ImportEndpoint::class,

        // Stock levels
        \Seeru\Mtac\Endpoints\Stock\ImportEndpoint::class,
        \Seeru\Mtac\Endpoints\Stock\UpdateSingleEndpoint::class,
    ],
];

<?php
/**
 * Rest API configuration
 */

return [
    'namespace' => 'mtac',

    'endpoints' => [
        // Categories
        \Seeru\Mtac\Endpoints\Category\ImportEndpoint::class,

        // Products
        // \Seeru\Mtac\Endpoints\Product\ImportEndpoint::class,
        // \Seeru\Mtac\Endpoints\Product\ImportSingleEndpoint::class,
        // \Seeru\Mtac\Endpoints\Product\UpdateSingleEndpoint::class,

        // Stock levels
        \Seeru\Mtac\Endpoints\Stock\ImportEndpoint::class,
        \Seeru\Mtac\Endpoints\Stock\UpdateSingleEndpoint::class,

        // Cron
        \Seeru\Mtac\Endpoints\CronEndpoint::class,
    ],
];

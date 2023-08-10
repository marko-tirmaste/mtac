<?php
/**
 * Rest API configuration
 */

return [
    'namespace' => 'mtac',

    'endpoints' => [
        // Products
        \Vdisain\Mtac\Endpoints\Product\ImportEndpoint::class,
        \Vdisain\Mtac\Endpoints\Product\ImportSingleEndpoint::class,
        \Vdisain\Mtac\Endpoints\Product\UpdateSingleEndpoint::class,

        // Stock levels
        \Vdisain\Mtac\Endpoints\Stock\ImportEndpoint::class,
        \Vdisain\Mtac\Endpoints\Stock\ImportSingleEndpoint::class,
        \Vdisain\Mtac\Endpoints\Stock\UpdateSingleEndpoint::class,

        // Cron
        \Vdisain\Mtac\Endpoints\CronEndpoint::class,
    ],
];

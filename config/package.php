<?php
/**
 * Package configuration
 */
defined('VDAI_PATH') or die;

return [
    'name' => __('M-Tac', 'seeru-mtac'),
    'description' => __('Syncs M-Tac products from XML Feed to WooCommerce.', 'seeru-mtac'),
    'slug' => 'mtac',
    'version' => '0.0.1',
    'type' => 'interface',

    /* --------------------------------------------------------------------------------
    | Admin specific configuration
    | --------------------------------------------------------------------------------
    |
    */

    'admin' => [
        'views' => [
            \Seeru\Mtac\Views\AdminView::class,
            \Seeru\Mtac\Views\DashboardView::class,
        ],
    ],

    /* --------------------------------------------------------------------------------
    | Site specific configuration
    | --------------------------------------------------------------------------------
    |
    */

    'site' => [
        //
    ],

    'options' => [
        \Seeru\Mtac\Controllers\OptionController::class,
    ],

    /* --------------------------------------------------------------------------------
    | Features
    | --------------------------------------------------------------------------------
    |
    */

    'features' => [
        'product' => [

        ]
    ]
];
<?php
/**
 * Package configuration
 */
defined('VDAI_PATH') or die;

return [
    'name' => __('M-Tac', 'vdisain-mtac'),
    'description' => __('Syncs M-Tac products from XML Feed to WooCommerce.', 'vdisain-mtac'),
    'slug' => 'mtac',
    'version' => '0.0.1',
    'type' => 'interface',

    /* --------------------------------------------------------------------------------
     | Admin specific configuration
     | -------------------------------------------------------------------------------- */
    'admin' => [
        'views' => [
            \Vdisain\Mtac\Views\AdminView::class,
        ],
    ],

    /* --------------------------------------------------------------------------------
     | Site specific configuration
     | -------------------------------------------------------------------------------- */
    'site' => [
        //
    ],

    'options' => [
        \Vdisain\Mtac\Controllers\OptionController::class,
    ]
];
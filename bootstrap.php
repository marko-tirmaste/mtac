<?php
/**
 * Bootstraps the M-Tac interface package
 */

defined('VDAI_PATH') or die;

require_once 'autoload.php';

if (empty(vi()->settings()->common['module']['mtac'])) {
    return;
}

if (!defined('VDAI_PATH_CACHE_MTAC')) {
    define('VDAI_PATH_CACHE_MTAC', WP_CONTENT_DIR . '/uploads/vdisain-api-interfaces/mtac');
}

/* --------------------------------------------------------------------------------
 | Providers
 | --------------------------------------------------------------------------------
 | 
 | Here are providers initialized and registrations called
 |
 | -------------------------------------------------------------------------------- */

vi()->make(\Seeru\Mtac\Providers\CronProvider::class)->register();
vi()->make(\Seeru\Mtac\Providers\ProductProvider::class)->register();
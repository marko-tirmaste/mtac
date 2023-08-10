<?php
/**
 * Bootstraps the M-Tac interface package
 */

defined('VDAI_PATH') or die;

require_once 'autoload.php';

if (empty(vi()->settings()->common['module']['mtac'])) {
    return;
}

/* --------------------------------------------------------------------------------
 | Providers
 | --------------------------------------------------------------------------------
 | 
 | Here are providers initialized and registrations called
 |
 | -------------------------------------------------------------------------------- */

vi()->make(\Vdisain\Mtac\Providers\CategoryProvider::class)->register();
vi()->make(\Vdisain\Mtac\Providers\CronProvider::class)->register();
vi()->make(\Vdisain\Mtac\Providers\ProductProvider::class)->register();
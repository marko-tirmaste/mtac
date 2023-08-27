<?php
/**
 * Provider class for package cron job registration
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Providers
 * @since 1.0.0 2023-05-17
 */
namespace Seeru\Mtac\Providers;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Controllers\ProductController;
use Seeru\Mtac\Controllers\StockController;

/**
 * Provider class for package cron job registration
 * 
 * @package Seeru\Mtac\Providers
 * @since 1.0.0 2023-05-17
 */
class CronProvider
{
    /**
     * Schedules the cron jobs
     */
    public function register(): void
    {
        $this->scheduleProductSync();
        $this->scheduleStockSync();
    }

    /**
     * Schedules the product sync job
     */
    private function scheduleProductSync(): void
    {
        if (empty(vi()->settings()->mtac['schedule']['products'])) {
            return;
        }

        if (!wp_next_scheduled('vdisain_interfaces/mtac/products')) {
            wp_schedule_event(time(), vi()->settings()->mtac['schedule']['products'], 'vdisain_interfaces/mtac/products');
        }

        add_action('vdisain_interfaces/mtac/products', [vi()->make(ProductController::class), 'import']);
    }

    /**
     * Schedules the stock sync job
     */
    private function scheduleStockSync(): void
    {
        if (empty(vi()->settings()->mtac['schedule']['stock'])) {
            return;
        }

        if (!wp_next_scheduled('vdisain_interfaces/mtac/stock')) {
            wp_schedule_event(time(), vi()->settings()->mtac['schedule']['stock'], 'vdisain_interfaces/mtac/stock');
        }

        add_action('vdisain_interfaces/mtac/stock', [vi()->make(StockController::class), 'importAll']);
    }
}
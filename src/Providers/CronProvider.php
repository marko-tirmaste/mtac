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
use Vdisain\Plugins\Interfaces\Support\Log\Log;

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
        if (empty(vi_config('mtac.schedule.products.time'))) {
            return;
        }

        if (!wp_next_scheduled('vdisain_interfaces/mtac/products/update')) {
            $time = explode(':', vi_config('mtac.schedule.products.time'));

            $timestamp = strtotime(
                sprintf(
                    '%s %s:%s:%s', 
                    date('Y-m-d'), 
                    str_pad($time[0] ?? '00', 2, '0', STR_PAD_LEFT),
                    str_pad($time[1] ?? '00', 2, '0', STR_PAD_LEFT),
                    str_pad($time[2] ?? '00', 2, '0', STR_PAD_LEFT),
                )
            );

            if ($timestamp < time()) {
                $timestamp += 86400;
            }

            wp_schedule_single_event($timestamp, 'vdisain_interfaces/mtac/products/update');
        }

        add_action('vdisain_interfaces/mtac/products/update', [$this, 'handle']);
        add_action('vdisain_interfaces/mtac/products/destroy', [vi()->make(ProductController::class), 'destroy']);
    }

    public function handle(): void
    {
        $result = vi()->make(ProductController::class)->import();

        Log::info('Product update executed', $result);

        if ($result['processed'] < $result['total']) {
            wp_schedule_single_event(time() + 120, 'vdisain_interfaces/mtac/products/update');
            return;
        }

        wp_schedule_single_event(time() + 120, 'vdisain_interfaces/mtac/products/destroy');
    }
}
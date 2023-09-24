<?php

/**
 * Endpoint for mtac cron sync (Categories, products and stock)
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Endpoints
 * @since 1.0.0 2023-07-28
 */

namespace Seeru\Mtac\Endpoints;

use Seeru\Mtac\Controllers\ProductController;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Rest\Endpoint;
use Vdisain\Plugins\Interfaces\Support\Contracts\Rest\EndpointContract;
use Vdisain\Plugins\Interfaces\Support\Log\Log;

set_time_limit(0);

/**
 * Endpoint for mtac cron sync (Categories, products and stock)
 * 
 * @package Seeru\Mtac\Endpoints
 * @since 1.0.0 2023-07-28
 */
class CronEndpoint extends Endpoint implements EndpointContract
{    
    /**
     * Endpoint arguments
     * 
     * @return array
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * Endpoint access authorization
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return !empty(vi_config('common.module.mtac'));
    }

    /**
     * Endpoint method
     * 
     * @return string
     */
    public function methods(): string
    {
        return 'GET';
    }

    /**
     * Respoints to the API call
     * 
     * @param \WP_REST_Request $request REST API request
     * 
     * @return \WP_REST_Response REST API response
     */
    public function respond(\WP_REST_Request $request): \WP_REST_Response
    {
        do_action('wpml_switch_language', vi()->locale());

        $this->syncProducts();

        return new \WP_REST_Response([
            'log' => Logger::array(),
        ]);
    }

    /**
     * Endpoint path
     * 
     * @return string
     */
    public function path(): string
    {
        return 'cron';
    }

    private function syncProducts(): void
    {
        Log::info('Sync products cron called.');

        if (empty(vi_config('mtac.schedule.products.time'))) {
            return;
        }

        $time = explode(':', vi_config('mtac.schedule.products.time'));
        $gap = $this->gap(vi_config('mtac.schedule.products.interval'));

        if (empty($time) || empty($gap)) {
            Logger::warn('Schedule for product sync not found!');
            return;
        }

        $start = strtotime(
            sprintf(
                '%s %s:%s:%s',
                date('Y-m-d'),
                str_pad($time[0] ?? '00', 2, '0', STR_PAD_LEFT),
                str_pad($time[1] ?? '00', 2, '0', STR_PAD_LEFT),
                str_pad($time[2] ?? '00', 2, '0', STR_PAD_LEFT),
            )
        ) /* - 10800 */;

        if (abs(time() - $start) < $gap / 2 || isset($_GET['start'])) {
            Log::info('Product update started.');
            update_option('vdisain_mtac_schedule_products_next_page', 1);
            update_option('vdisain_mtac_schedule_products_running', 1);
        }

        $isRunning = get_option('vdisain_mtac_schedule_products_running');

        $next = (int) get_option('vdisain_mtac_schedule_products_last') + $gap;

        if (vi()->isVerbose()) {
            Logger::describe(sprintf(
                'Scheduled product sync: %s, now: %s, gap: %s, started: %s, is running: %s, updating: %s.', 
                date('Y-m-d H:i:s', $next),
                date('Y-m-d H:i:s'),
                $gap,
                date('Y-m-d H:i:s', $start),
                empty($isRunning) ? 'no' : 'yes',
                empty($isRunning) || $next > time() ? 'no' : 'yes'
            ));
        }

        if (empty($isRunning) || $next > time()) {
            return;
        }

        $result = vi()->make(ProductController::class)->import();
        Logger::describe('Product sync at ' . date('Y-m-d H:i:s'));

        Log::info('Product update executed', $result);

        if ($result['processed'] >= $result['total']) {
            Log::info('Product update completed.');
            vi()->make(ProductController::class)->destroy();
            Logger::describe('Product deleted at ' . date('Y-m-d H:i:s'));
            delete_option('vdisain_mtac_schedule_products_running');
        }
    }

    private function gap(string $key): int
    {
        $schedule = apply_filters('cron_schedules', []);
        return !empty($schedule[$key]) ? $schedule[$key]['interval'] : 0;
    }
}
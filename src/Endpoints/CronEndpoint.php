<?php

/**
 * Endpoint for mtac cron sync (Categories, products and stock)
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Endpoints
 * @since 1.0.0 2023-07-28
 */

namespace Vdisain\Mtac\Endpoints;

use Vdisain\Mtac\Controllers\ProductController;
use Vdisain\Mtac\Controllers\StockController;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Rest\Endpoint;
use Vdisain\Plugins\Interfaces\Support\Contracts\Rest\EndpointContract;

set_time_limit(0);

/**
 * Endpoint for mtac cron sync (Categories, products and stock)
 * 
 * @package Vdisain\Mtac\Endpoints
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
        $this->syncStock();

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
        if (empty(vi_config('mtac.schedule.products'))) {
            return;
        }

        $gap = $this->gap(vi_config('mtac.schedule.products'));

        if (empty($gap)) {
            Logger::warn('Schedule for product sync not found!');
            return;
        }

        $next = (int) get_option('vdisain_mtac_schedule_products_last') + $gap;

        if (vi()->isVerbose()) {
            Logger::describe(sprintf(
                'Scheduled product sync: %s (%s)', 
                date('Y-m-d H:i:s', $next),
                $next > time() ? 'skip' : 'execute'
            ));
        }

        if ($next > time()) {
            return;
        }

        Logger::describe('Product sync at ' . date('Y-m-d H:i:s'));

        vi()->make(ProductController::class)->import();
    }

    private function syncStock(): void
    {
        if (empty(vi_config('mtac.schedule.stock'))) {
            return;
        }

        $gap = $this->gap(vi_config('mtac.schedule.stock'));

        if (empty($gap)) {
            Logger::warn('Schedule for stock sync not found!');
            return;
        }

        $next = (int) get_option('vdisain_mtac_schedule_stock_last') + $gap;

        if (vi()->isVerbose()) {
            Logger::describe(sprintf(
                'Scheduled stock sync: %s (%s)', 
                date('Y-m-d H:i:s', $next),
                $next > time() ? 'skip' : 'execute'
            ));
        }

        if ($next > time()) {
            return;
        }

        Logger::describe('Stock sync at ' . date('Y-m-d H:i:s'));

        vi()->make(StockController::class)->importAll();
    }

    private function gap(string $key): ?int
    {
        $schedule = apply_filters('cron_schedules', []);
        return !empty($schedule[$key]) ? $schedule[$key]['interval'] : null;
    }
}
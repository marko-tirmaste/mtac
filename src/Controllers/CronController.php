<?php

declare(strict_types=1);

namespace Seeru\Mtac\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use Vdisain\Plugins\Interfaces\Support\Logger;

class CronController
{
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'products' => $this->syncProducts(),
            'log' => Logger::array(),
        ]);
    }

    protected function syncProducts(): ?array
    {
        if (empty(vi_config('mtac.schedule.products.interval'))) {
            return null;
        }

        $gap = $this->gap(vi_config('mtac.schedule.products.interval'));

        $isRunning = get_option('vdisain_mtac_schedule_products_running');

        $next = (int) get_option('vdisain_mtac_schedule_products_last') + $gap;

        if ($isRunning && $next + 3600 < time()) {
            // Process has been running for more than a hour. Probably stuck.
            $isRunning = false;
        }

        Logger::dump([
            'isRunning' => $isRunning,
            'next' => date('Y-m-d H:i:s', $next),
            'time' => date('Y-m-d H:i:s', time()),
            'gap' => $gap,
        ]);

        if (!empty($isRunning) || $next > time()) {
            Logger::describe('Product sync is already running.');
            return null;
        }

        update_option('vdisain_mtac_schedule_products_running', 1);

        $result = vi()->make(ProductController::class)->import();

        if ($result['processed'] >= $result['total']) {
            vi()->make(ProductController::class)->destroy();
            update_option('vdisain_mtac_schedule_products_next_page', 1);
            Logger::describe('Reseting product sync.');
        }

        delete_option('vdisain_mtac_schedule_products_running');

        return $result;
    }

    protected function gap(string $key): int
    {
        $schedule = apply_filters('cron_schedules', []);
        return !empty($schedule[$key]) ? $schedule[$key]['interval'] : 0;
    }
}
<?php

declare(strict_types=1);

namespace Seeru\Mtac\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Log\Log;

class CronController
{
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $this->syncProducts();

        return new WP_REST_Response([
            'log' => Logger::array(),
        ]);
    }

    protected function syncProducts(): void
    {
        if (empty(vi_config('mtac.schedule.products.interval'))) {
            return;
        }

        $gap = $this->gap(vi_config('mtac.schedule.products.interval'));

        $isRunning = get_option('vdisain_mtac_schedule_products_running');

        $next = (int) get_option('vdisain_mtac_schedule_products_last') + $gap;

        Logger::dump([
            'isRunning' => $isRunning,
            'next' => $next,
            'gap' => $gap,
        ]);

        if (empty($isRunning) || $next > time()) {
            return;
        }

        update_option('vdisain_mtac_schedule_products_running', 1);

        $result = vi()->make(ProductController::class)->import();

        Log::info('Product update executed', $result);

        if ($result['processed'] >= $result['total']) {
            Log::info('Product update completed.');
            vi()->make(ProductController::class)->destroy();
            update_option('vdisain_mtac_schedule_products_next_page', 1);
        }

        delete_option('vdisain_mtac_schedule_products_running');
    }

    protected function gap(string $key): int
    {
        $schedule = apply_filters('cron_schedules', []);
        return !empty($schedule[$key]) ? $schedule[$key]['interval'] : 0;
    }
}
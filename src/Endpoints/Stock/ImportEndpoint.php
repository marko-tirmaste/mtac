<?php

/**
 * Endpoint for syncing mtac stock levels to Woocommerce stock levels
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Endpoints\Stock
 * @since 1.3.0 2023-05-15
 */

namespace Vdisain\Mtac\Endpoints\Stock;

use Vdisain\Mtac\Controllers\StockController;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Rest\Endpoint;
use Vdisain\Plugins\Interfaces\Support\Contracts\Rest\EndpointContract;

/**
 * Endpoint for syncing mtac stock levels to Woocommerce stock levels
 * 
 * @package Vdisain\Mtac\Endpoints\Stock
 * @since 1.3.0 2023-05-15
 */
class ImportEndpoint extends Endpoint implements EndpointContract
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
        return true;
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
        return new \WP_REST_Response([
            ...(vi()->make(StockController::class)->import()),
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
        return 'stock/import';
    }
}
<?php

/**
 * Endpoint for syncing mtac product to Woocommerce product
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Endpoints\Product
 * @since 1.0.0 2023-04-17
 */

namespace Seeru\Mtac\Endpoints\Product;

use Seeru\Mtac\Controllers\ProductController;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Rest\Endpoint;
use Vdisain\Plugins\Interfaces\Support\Contracts\Rest\EndpointContract;

/**
 * Endpoint for syncing mtac product to Woocommerce product
 * 
 * @package Seeru\Mtac\Endpoints\Product
 * @since 1.0.0 2023-04-17
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
        do_action('wpml_switch_language', vi()->locale());

        return new \WP_REST_Response([
            ...(vi()->make(ProductController::class)->import()),
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
        return 'product/import';
    }
}
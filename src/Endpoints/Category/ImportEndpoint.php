<?php

/**
 * Endpoint for syncing M-Tac categories to Woocommerce categories
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Endpoints\Category
 * @since 1.0.0
 */

namespace Seeru\Mtac\Endpoints\Category;

use Seeru\Mtac\Controllers\CategoryController;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Rest\Endpoint;
use Vdisain\Plugins\Interfaces\Support\Contracts\Rest\EndpointContract;

/**
 * Endpoint for syncing M-Tac categories to Woocommerce categories
 * 
 * @package Seeru\Mtac\Endpoints\Category
 * @since 1.0.0
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
            ...(vi()->make(CategoryController::class)->import()),
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
        return 'category/import';
    }
}
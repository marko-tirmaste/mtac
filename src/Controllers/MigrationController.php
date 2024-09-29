<?php

declare(strict_types=1);

namespace Seeru\Mtac\Controllers;

use Seeru\Mtac\Services\MigrationService;
use Vdisain\Plugins\Interfaces\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') or die;

class MigrationController
{
    public function __construct(private MigrationService $service)
    {
    }

    public function migrate(WP_REST_Request $request): WP_REST_Response
    {
        $this->service->updateVariableProductSKUs();
        $this->service->updateAttachmentsMeta();


        return new WP_REST_Response([
            'message' => 'Migration completed',
            'log' => Logger::array(),
        ]);
    }
}
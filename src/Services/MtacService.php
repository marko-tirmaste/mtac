<?php
/**
 * Service class for connecting to mtac and handling products
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Services
 * @since 1.3.0 2023-05-11
 */
namespace Vdisain\Mtac\Services;

defined('VDAI_PATH') or die;

use GuzzleHttp\Client;
use Vdisain\Plugins\Interfaces\Models\Settings;
use Vdisain\Plugins\Interfaces\Exceptions\MissingSettingsException;

/**
 * Service class for connecting to mtac and handling products
 * 
 * @package Vdisain\Mtac\Services
 * @since 1.3.0 2023-05-11
 */
class MtacService
{
    public function __construct()
    {
        $this->settings = vi()->settings();

        $this->xmlFeedUrl = $this->settings->mtac['xml_url'];
        if (empty($this->xmlFeedUrl)) {
            throw new MissingSettingsException();
        }

        $this->client = new Client([
            'base_uri' => $this->xmlFeedUrl,
            'headers' => [
                'Accept' => 'application/xml',
                'Accept-Encoding' => 'gzip',
                'decode_content' => false
            ],
        ]);
    }

    protected Client $client;
    protected Settings $settings;
    protected string $xmlFeedUrl;
}
<?php
/**
 * Service class for connecting to mtac and handling products
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Services
 * @since 1.0.0 2023-05-11
 */
namespace Seeru\Mtac\Services;

defined('VDAI_PATH') or die;

use GuzzleHttp\Client;
use Vdisain\Plugins\Interfaces\Exceptions\MissingSettingsException;

/**
 * Service class for connecting to mtac and handling products
 * 
 * @package Seeru\Mtac\Services
 * @since 1.0.0 2023-05-11
 */
class MtacService
{    
    public function __construct()
    {
        $this->xmlFeedUrl = vi_config('mtac.xml_url');
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

    const CACHE_EXPIRE_TIME = 3600;

    protected Client $client;
    protected string $xmlFeedUrl;

    protected function isCached(): bool
    {
        return file_exists(VDAI_PATH_CACHE_MTAC . '/products.json')
            && filemtime(VDAI_PATH_CACHE_MTAC . '/products.json') + static::CACHE_EXPIRE_TIME > time();
    }

    protected function readCache(): array
    {
        return json_decode(file_get_contents(VDAI_PATH_CACHE_MTAC . '/products.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function writeCache(array $data): void
    {
        if (!is_dir(VDAI_PATH_CACHE_MTAC)) {
            mkdir(VDAI_PATH_CACHE_MTAC, 0777, true);
        }

        file_put_contents(VDAI_PATH_CACHE_MTAC . '/products.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    


}
<?php
/**
 * Service class for connecting to mtac and handling products
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Services
 * @since 1.3.0 2023-05-09
 */
namespace Vdisain\Mtac\Services;

defined('VDAI_PATH') or die;

use Vdisain\Plugins\Interfaces\Support\Collection;

/**
 * Service class for connecting to mtac and handling products
 * 
 * @package Vdisain\Mtac\Services
 * @since 1.3.0 2023-05-09
 */
class ProductService extends MtacService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets all products from mtac
     * 
     * @return Collection
     */
    public function get(?array $request = []): Collection
    {
        ///$response = $this->client->get('');
        //$data = $response->getBody()->getContents();
        $data = file_get_contents(__DIR__ . '/data.xml');
        $xml = json_decode(json_encode(simplexml_load_string(str_replace(['<g:', '</g:'], ['<', '</'], $data), 'SimpleXMLElement', LIBXML_NOCDATA)), true, 512, JSON_THROW_ON_ERROR);

        if (!file_exists(__DIR__ . '/data.xml')) {
            file_put_contents(__DIR__ . '/data.xml', $data);
        }

        //if (!file_exists(__DIR__ . '/data.json')) {
            file_put_contents(__DIR__ . '/data.json', json_encode($xml, JSON_PRETTY_PRINT));
        //}

        return vi_collect($xml['entry'] ?? []);
    }

    /**
     * Gets single product from mtac
     * 
     * @param int $id mtac item code
     * 
     * @return array|null
     */
    public function find(int $id): ?array
    {
        return $this->get()
            ->filter(function (array $item) use ($id): bool {
                return (int) $item['id'] === $id;
            })
            ->first();
    }
}
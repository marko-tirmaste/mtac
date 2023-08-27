<?php
/**
 * Service class for connecting to mtac and handling products
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Services
 * @since 1.0.0 2023-05-09
 */
namespace Seeru\Mtac\Services;

defined('VDAI_PATH') or die;

use Vdisain\Plugins\Interfaces\Support\Collection;

/**
 * Service class for connecting to mtac and handling products
 * 
 * @package Seeru\Mtac\Services
 * @since 1.0.0 2023-05-09
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
        if ($this->isCached()) {
            return vi_collect($this->readCache());
        }

        $response = $this->client->get('');
        $data = $response->getBody()->getContents();
        $xml = json_decode(
            json_encode(
                simplexml_load_string(
                    str_replace(
                        ['<g:', '</g:', '<![CDATA[]]>'], 
                        ['<', '</', '0'], 
                        $data
                    ), 
                    'SimpleXMLElement', 
                    LIBXML_NOCDATA
                )
            ),
            true, 
            512, 
            JSON_THROW_ON_ERROR
        );

        $this->writeCache($xml['entry']);

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
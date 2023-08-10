<?php
/**
 * Mapper class for mtac product
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Mappers
 * @since 1.3.0 2023-05-09
 */
namespace Vdisain\Mtac\Mappers;

defined('ABSPATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\MapperContract;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Mapper;

/**
 * Mapper class for mtac product
 * 
 * @package Vdisain\Mtac\Mappers
 * @since 1.3.0 2023-05-09
 */
class ProductMapper extends Mapper implements MapperContract
{
    /**
     * Maps mtac product data to the WooCommerce product data
     * 
     * @param array $data mtac product data
     * 
     * @return array
     */
    public function map($data): array
    {
        $map = [
            'id' => $this->mapId($data['id']),
            'parent_id' => null,
            'status' => 'publish',
            'sku' => $data['gtin'] ?? null,
            'type' => $this->mapType($data),
            'quantity' => $data['quantity'] ?? 0,
            'variations' => !empty($data['id']) ? $this->mapVariations($data['sizes']) : null,
            'meta' => [
                '_mtac_id' => $data['id'],
                '_condition' => $data['condition'] ?? null
            ],
        ];

        $isNew = empty($map['id']);

        if ($this->isMapping('name', $isNew)) {
            $map['name'] = [
                'et' => $data['title'],
            ];
        }

        if ($this->isMapping('description', $isNew)) {
            $map['description'] = $data['description'] ?? null;
        }

        if ($this->isMapping('price', $isNew)) {
            $map['price'] = $this->mapPrice($data);
        }

        if ($this->isMapping('attributes', $isNew)) {
            $map['attributes'] = $this->mapAttributes($data);
        }

        if ($this->isMapping('categories', $isNew)) {
            $map['categories'] = $this->mapCategories($data);
        }

        if ($this->isMapping('images', $isNew) && !empty($data['image_link'])) {
            $map['images'] = [
                $data['image_link'],
                ...($data['additional_image_link'] ?? []),
            ];
        }


        return $map;
    }


    /**
     * Checks if field should be mapped
     * 
     * @param string $field Field name
     * @param bool $isNew Is new product
     * 
     * @return bool
     */
    protected function isMapping(string $field, bool $isNew): bool
    {
        $rule = vi_config('mtac.field.' . $field, 'import-update');
        return !(empty($rule) || ($rule === 'import' && !$isNew));
    }
    private function mapVariations(array $data): array 
    {
        return vi_collect($data['size'])->map(function(array $data): array {
            return $this->map($data);
        })->toArray();
    }

    /**
     * Maps attributes
     * 
     * @param array $data M-Tac product data
     * 
     * @return array|null
     */
    private function mapAttributes(array $data): ?array
    {
        return [
           'brand' => new AttributeMapper(['key' => 'brand', 'data' => $data,]),
           'color' => new AttributeMapper(['key' => 'color', 'data' => $data,]),
           'size' => new AttributeMapper(['key' => 'size', 'data' => $data,]),
        ];
    }

    /**
     * Maps categories
     * 
     * @param array $data M-Tac product data
     * 
     * @return array
     */
    private function mapCategories(array $data): ?array
    {
        return [];
    }

    /**
     * Gets WooCommerce product ID
     * 
     * @param string $mtacId M-Tac product ID
     * 
     * @return int
     */
    private function mapId(string $mtacId): ?int
    {
        /* $id = (int) wc_get_product_id_by_sku($mtacId);

        if (!empty($id)) {
            return $id;
        } */

        /** @var \WP_Post */
        $product = vi_collect(
            get_posts([
                'post_type' => ['product', 'product_variation'],
                'meta_query' => [
                    [
                        'key' => '_mtac_id',
                        'value' => $mtacId,
                        'compare' => '=',
                    ],
                ],
                'posts_per_page' => -1,
                'post_status' => [
                    'draft',
                    'future',
                    'trash',
                    'pending',
                    'private',
                    'publish',
                ],
                'order' => 'ASC',
                'orderby' => 'date',
            ])
        )->first();

        if (vi()->isVerbose()) {
            Logger::describe("ID: {$product->ID}");
        }

        return $product->ID ?? null;
    }

    /**
     * Maps product price, adds markup and VAT if necessery
     * 
     * @param array $data Product data
     * 
     * @return float|null
     */
    private function mapPrice($data): ?float
    {
        if (!isset($data['price'])) {
            return null;
        }

        $price = (float) $data['price'];

        $markup = vi_config('mtac.markup');
        if (!empty($markup)) {
            $price += ($price / 100) * $markup;
        }

        $vat = vi_config('mtac.vat');
        if (!empty($vat)) {
            $price += ($price / 100) * $vat;
        }

        return $price;
    }

    /**
     * Maps product type
     * 
     * @param array $data M-Tac product data
     * 
     * @return string
     */
    private function mapType(array $data): string
    {   
        return /* !empty($data['id']) ? ProductRepository::TYPE_VARIABLE : */ ProductRepository::TYPE_SIMPLE;
    }
}
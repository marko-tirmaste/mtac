<?php
/**
 * Mapper class for mtac product
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0 2023-05-09
 */
namespace Seeru\Mtac\Mappers;
use Vdisain\Plugins\Interfaces\Repositories\CategoryRepository;

defined('ABSPATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\MapperContract;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Mapper;
use WP_Term;

/**
 * Mapper class for mtac product
 * 
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0 2023-05-09
 */
class ProductMapper extends Mapper implements MapperContract
{
    protected static Collection $categories;

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
            'id' => $this->mapId($data),
            'parent_id' => null,
            //'sku' => $data['gtin'] ?? null,
            'type' => $this->mapType($data),
            //'quantity' => $data['quantity'] ?? 0,
            'manage_stock' => false,
            'stock_status' => !empty($data['availability']) && $data['availability'] === 'in stock' ? 'instock' : 'outofstock',
            'meta' => [
                '_mtac_id' => empty($data['variations']) ? $data['id'] : null,
                '_condition' => $data['condition'] ?? null,
                //'_sku' => !empty($data['gtin']) ? $data['gtin'] : null,
            ],
        ];

        $isNew = empty($map['id']);

        if ($isNew) {
            $map['status'] = empty($data['parent_id']) && !empty(vi_config('mtac.new_status')) ? vi_config('mtac.new_status') : 'publish';
        }

        if (!empty($data['gtin'])) {
            $map['meta']['_sku'] = $data['gtin'];
        }

        if (!empty($data['parent_id'])) {
            $map['parent_id'] = $data['parent_id'];
        }

        /* if (!empty($data['variations'])) {
            $map['variations'] = $data['variations']->map(function (array $variation): ProductMapper {
                return new ProductMapper($variation);
            });
        } */

        if ($this->isMapping('name', $isNew)) {
            $map['name'] = [
                'et' => $data['title'],
            ];
        }

        if ($this->isMapping('description', $isNew)) {
            $map['description'] = ['et' => $data['description'] ?? null];
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
                ...(empty($data['additional_image_link'])
                    ? []
                    : (is_string($data['additional_image_link']) ? [$data['additional_image_link']] : $data['additional_image_link'])
                ),
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
        return vi_collect($data['size'])->map(function (array $data): array {
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
        $attributes = [];

        if (!empty($data['brand'])) {
            $attributes['brand'] = new BrandToAttributeMapper($data['brand']);
        }

        if (!empty($data['color'])) {
            $attributes['color'] = new ColorToAttributeMapper(
                !empty($data['variations']) ? $data['variations']->pluck('color') : vi_collect([$data['color']])
            );
        }

        if (!empty($data['size'])) {
            $attributes['size'] = new SizeToAttributeMapper(
                !empty($data['variations']) ? $data['variations']->pluck('size') : vi_collect([$data['size']])
            );
        }

        return $attributes;
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
        if (empty(static::$categories)) {
            static::$categories = vi()->make(CategoryRepository::class)->all();
        }

        $path = array_map(
            function (string $name): string {
                return sanitize_title($name);
            },
            explode(' / ', $data['product_type'])
        );

        return static::$categories
            ->filter(function (WP_Term $term) use ($path): bool {
                return in_array($term->slug, $path);
            })
            ->pluck('term_id')
            ->values()
            ->all();
    }

    /**
     * Gets WooCommerce product ID
     * 
     * @param array $data M-Tac product data
     * 
     * @return int
     */
    private function mapId(array $data): ?int
    {
        if (!empty($data['gtin'])) {
            $id = (int) wc_get_product_id_by_sku($data['gtin']);

            if (!empty($id)) {
                return $id;
            }
        }

        /** @var \WP_Post */
        $product = vi_collect(
            get_posts([
                'post_type' => ['product', 'product_variation'],
                'meta_query' => [
                    [
                        'key' => '_mtac_id',
                        'value' => $data['id'],
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

        //$id = !empty($product->post_parent) ? $product->post_parent : ($product->ID ?? null);
        $id = !empty($data['variations']) ? ($product->post_parent ?? null) : ($product->ID ?? null);

        if (vi()->isVerbose()) {
            Logger::describe(sprintf('ID: %s', $id ?? 'not found'));
        }

        return $id;
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

        if (vi()->isVerbose()) {
            Logger::describe(sprintf('Price: %s €', $price));
        }

        $markup = vi_collect(vi_config('mtac.markups', []))
            ->sortByDesc('max')
            ->filter(fn(array $markup): bool => $markup['max'] <= $data['price'])
            ->first();

        if (!empty($markup)) {
            $price += ($price / 100) * $markup['markup'];
            if (vi()->isVerbose()) {
                Logger::describe(sprintf('+ markup: %s €', ($price / 100) * $markup['markup']));
            }
        }

        $vat = vi_config('mtac.vat');
        if (!empty($vat)) {
            $price += ($price / 100) * $vat;
            if (vi()->isVerbose()) {
                Logger::describe(sprintf('+ VAT: %s €', ($price / 100) * $vat));
            }
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
        return !empty($data['variations']) ? ProductRepository::TYPE_VARIABLE : ProductRepository::TYPE_SIMPLE;
    }
}
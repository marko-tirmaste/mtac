<?php

declare(strict_types=1);

namespace Seeru\Mtac\Models;

use \WP_Term;
use Seeru\Mtac\Services\MediaService;
use Vdisain\Plugins\Interfaces\Support\Arr;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Cache\Cache;
use Vdisain\Plugins\Interfaces\Models\Product as BaseProduct;

defined('VDAI_PATH') or die;

class Product extends BaseProduct
{
    protected array $fillableMeta = [
        '_mtac_id',
        '_ean',
        '_sku',
        '_net_weight',
        '_volume',
        'volume_value',
        '_unit',
        '_created',
        '_updated',
    ];

    protected array $attributeMap = [
        'brand' => ['et' => 'Bränd'],
        'color' => ['et' => 'Värv'],
        'size' => ['et' => 'Suurus'],
    ];

    protected ?int $mtacId = null;
    protected ?int $mtacParentId = null;
    protected ?array $name = [];

    public function bind(array $data): self
    {
        parent::bind($this->map($data));

        return $this;
    }

    public function mtacId(): ?int
    {
        return $this->mtacId;
    }

    public function mtacParentId(): ?int
    {
        return $this->mtacParentId;
    }

    public function getName(string $language)
    {
        return $this->name[$language] ?? null;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function update(array $data = []): void
    {
        $product = $this->products['et']?->getWcProduct() ?? null;

        if (!$product) {
            $product = !empty($data['gtin']) ? wc_get_product(wc_get_product_id_by_sku($data['gtin'])) : null;
        }

        if (!$product) {
            return;
        }

        $regularPrice = (string) $this->mapPrice($data);
        $salePrice = (string) $this->mapPrice($data, 'sale_price');

        $product->set_stock_status(!empty($data['availability']) && $data['availability'] === 'in stock' ? 'instock' : 'outofstock');
        $product->set_price($salePrice ?? $regularPrice);
        $product->set_regular_price($regularPrice);
        $product->set_sale_price($salePrice);
        $product->save();

        if ($this->allowed('images', 'import-update') && (empty($data['type']) || $data['type'] !== 'variation')) {
            vi()->make(MediaService::class)->download($product->get_id(), $this->mapMtacImages($data));
        }
    }

    protected function map(array $data): array
    {
        // Logger::describe('Data', $data);
        // $withVat = !empty(vi_config('erply.prices_with_vat')) ? 'WithVAT' : '';

        $this->mtacId = (int) $data['id'];
        $this->mtacParentId = (int) ($data['item_group_id'] ?? 0);

        $this->name = [
            'et' => $data['Desc'] ?? null,
            // 'en' => $data['Desc'] ?? null,
        ];

        $regularPrice = $this->mapPrice($data);
        $salePrice = $this->mapPrice($data, 'sale_price');

        $map = [
            'status' => 'publish',

            'type' => array_key_exists('type', $data) ? $data['type'] : static::TYPE_SIMPLE,

            'categories' => $this->mapMtacCategories($data),

            'name' => [
                'et' => $data['title'] ?? null,
                // 'en' => $data['Desc'] ?? null,
            ],
            'description' => [
                'et' => $data['description'] ?? null,
            ],

            'manage_stock' => false,
            'stock_status' => !empty($data['availability']) && $data['availability'] === 'in stock' ? 'instock' : 'outofstock',

            'price' => $salePrice ?? $regularPrice,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,

            'meta' => [
                '_mtac_id' => $data['id'],
                '_sku' => $data['gtin'] ?? null,
                '_external_link' => $data['link'] ?? null
            ],

            'attributes' => $this->mapAttributes($data),
            'images' => $this->mapMtacImages($data),
        ];

        return apply_filters('vdhub-mtac/product/map', $map, $data);
    }

    protected function mapAttributes(array $data): array
    {
        $attributes = vi_collect();

        $exclude = [
            'id',
            'title',
            'description',
            'google_product_category',
            'product_type',
            'link',
            'image_link',
            'additional_image_link',
            'condition',
            'availability',
            'price',
            'sale_price',
            'gtin',
            'mpn',
            'item_group_id',
            'type',
        ];

        foreach (Arr::except($data, $exclude) as $key => $value) {
            if (!array_key_exists($key, $this->attributeMap)) {
                Logger::warn("Unknown attribute key: {$key}");
                continue;
            }

            if (empty($value)) {
                continue;
            }

            $name = $this->attributeMap[$key] ?? $key;

            $attributes->push([
                'taxonomy' => wc_attribute_taxonomy_name($name['et']),
                'name' => $name,
                'options' => $this->mapMtacAttributeOptions($value),
                'is_public' => true,
                'is_variation' => $key !== 'brand',
            ]);
        }

        return $attributes->toArray();
    }

    private function mapMtacAttributeOptions(array|string $data): array
    {
        if (is_array($data)) {
            return array_map(
                fn (string $value): array => [
                    'name' => [
                        'et' => $value
                    ],
                ],
                $data
            );
        }

        return [
            [
                'name' => [
                    'et' => $data
                ],
            ],
        ];
    }

    protected function mapMtacCategories(array $data): array
    {
        $path = array_map(
            fn (string $name): string => sanitize_title($name),
            explode(' > ', $data['product_type'])
        );

        return Cache::get('woocommerce_categories')
            ->filter(fn (WP_Term $term): bool => in_array($term->slug, $path))
            ->pluck('term_id')
            ->values()
            ->all();

    }

    protected function mapMtacImages(array $data): Collection
    {
        $images = vi_collect(!empty($data['image_link']) ? [new Image(['url' => $data['image_link']])] : []);

        if (array_key_exists('type', $data) && $data['type'] === 'variation') {
            return $images;
        }

        if (array_key_exists('additional_image_link', $data)) {
            $images = $images->merge(array_map(
                fn (string $link): Image => new Image(['url' => $link]),
                (array) ($data['additional_image_link'] ?? [])
            ));
        }

        return $images;
    }

    /**
     * Maps product price, adds markup and VAT if necessery
     *
     * @param array $data Product data
     *
     * @return float|null
     */
    private function mapPrice($data, string $key = 'price'): ?float
    {
        if (!isset($data[$key])) {
            return null;
        }

        $price = (float) $data[$key];

        $markup = vi_collect(vi_config('mtac.markups', []))
            ->sortByDesc('max')
            ->filter(fn(array $markup): bool => $markup['max'] <= $price)
            ->first();

        if (!empty($markup)) {
            $price += ($price / 100) * $markup['markup'];
        }

        $vat = vi_config('mtac.vat');
        if (!empty($vat)) {
            $price += ($price / 100) * $vat;
        }

        return $price;
    }

    public function rules(): array
    {
        return [
            'name' => vi_config('mtac.field.name', 'import-update'),
            'short_description' => vi_config('mtac.field.short_description', 'import-update'),
            'description' => vi_config('mtac.field.description', 'import-update'),
            'categories' => vi_config('mtac.field.categories', 'import-update'),
            'price' => vi_config('mtac.field.price', 'import-update'),
            'sale_price' => vi_config('mtac.field.sale_price', 'import-update'),
            'measurements' => vi_config('mtac.field.measurements', 'import-update'),
            'unit' => vi_config('mtac.field.unit', 'import-update'),
            'images' => vi_config('mtac.field.images', 'import-update'),
            'codes' => vi_config('mtac.field.codes', 'import-update'),
        ];
    }

    public function save(): void
    {
        $this->saveProduct(vi()->locale());

        if ($this->isVariation()) {
            return; // For now, don't download media for variations
        }

        $attachmentIds = [];

        if (
            $this->allowed('images', 'import-update')
            || ($this->allowed('images', value: 'import') && empty($this->products[vi()->locale()]->getWcProduct()?->get_id()))
        ) {
            $attachmentIds = vi()->make(MediaService::class)->download($this->products[vi()->locale()]->getWcProduct()->get_id(), $this->images);
        }

        foreach ($this->products as $language => $product) {
            if ($language === vi()->locale()) {
                continue;
            }
            $this->saveProduct($language, $attachmentIds);
        }

        $this->setWpmlDetails();
        // $this->setAttributeWpmlDetails();
    }
}
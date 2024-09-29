<?php

declare(strict_types=1);

namespace Seeru\Mtac\Models;

use \WP_Term;
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

        $map = [
            'status' => 'publish',

            'type' => array_key_exists('type', $data) && $data['type'] === 'variable' ? static::TYPE_VARIABLE : static::TYPE_SIMPLE,

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

            'price' => $this->mapPrice($data),

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

            $attributes->push([
                'name' => $this->attributeMap[$key] ?? $key,
                'options' => $this->mapMtacAttributeOptions($value),
                'visible' => true,
                'variation' => false,
            ]);
        }

        return $attributes->toArray();
    }

    private function mapMtacAttributeOptions(array|string $data): array
    {
        if (is_array($data)) {
            return array_map(
                fn (string $value): array => [
                    'et' => $value,
                ],
                $data
            );
        }

        return [
            [
                'et' => $data,
            ],
        ];
    }

    protected function mapMtacCategories(array $data): array
    {
        $path = array_map(
            fn (string $name): string => sanitize_title($name),
            explode(' / ', $data['product_type'])
        );

        return Cache::get('woocommerce_categories')
            ->filter(fn (WP_Term $term): bool => in_array($term->slug, $path))
            ->pluck('term_id')
            ->values()
            ->all();
    }

    protected function mapMtacImages(array $data): Collection
    {
        $images = vi_collect([new Image(['url' => $data['image_link']])]);

        if (array_key_exists('type', $data) && $data['type'] === 'variation') {
            return $images;
        }

        if (array_key_exists('additional_image_link', $data)) {
            $images = $images->merge(array_map(
                fn (string $link): Image => new Image(['url' => $link]),
                $data['additional_image_link'] ?? []
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
    private function mapPrice($data): ?float
    {
        if (!isset($data['price'])) {
            return null;
        }

        $price = (float) $data['price'];

        Logger::describe(sprintf('Price: %s €', $price), null, 1);

        $markup = vi_collect(vi_config('mtac.markups', []))
            ->sortByDesc('max')
            ->filter(fn(array $markup): bool => $markup['max'] <= $data['price'])
            ->first();

        if (!empty($markup)) {
            $price += ($price / 100) * $markup['markup'];
            Logger::describe(sprintf('+ markup: %s €', ($price / 100) * $markup['markup']), null, 1);
        }

        $vat = vi_config('mtac.vat');
        if (!empty($vat)) {
            $price += ($price / 100) * $vat;
            Logger::describe(sprintf('+ VAT: %s €', ($price / 100) * $vat), null, 1);
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
}
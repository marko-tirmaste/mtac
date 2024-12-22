<?php

declare(strict_types=1);

namespace Seeru\Mtac\Services;

use Seeru\Mtac\Models\Product;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Cache\Cache;
use Vdisain\Plugins\Interfaces\Repositories\CategoryRepository;

defined('ABSPATH') or die;

class ProductSyncService
{
    protected int $added = 0;
    protected int $updated = 0;
    protected int $deleted = 0;
    protected int $processed = 0;

    protected Collection $products;

    protected Collection $parents;

    public function __construct(
        protected ProductService $service,
    ) {
        add_filter('vdhub/media/service', fn (): string => MediaService::class);
    }

    public function syncProducts(int $page = 1, int $perPage = 25): array
    {
        $this->parents = new Collection();

        $this->products = $this->service->get()
            ->sortBy(fn(array $product): int => $product['id'] === ($product['item_group_id'] ?? 0) ? 0 : 1);

        Cache::put('woocommerce_categories', $this->getCategories());

        $this->slice($page, $perPage)
            ->each(function (array $product) {
                $this->process($product);
            });

        $this->parents->each(function (Product $product): void {
            $product->sync();
        });

        $total = $this->products->count();

        return [
            'processed' => $this->processed,
            'added' => $this->added,
            'updated' => $this->updated,
            'total' => $total,
            'pages' => ceil($total / $perPage),
        ];
    }

    protected function process(array $data): void
    {
        if (empty($data['type']) && $this->isVariable($data)) {
            $this->processVariable($data);
            $data['type'] = 'variation';
        } elseif (empty($data['type']) && $this->isVariation($data)) {
            $data['type'] = 'variation';
        }

        // $product = new Product(['meta' => ['key' => '_sku', 'value' => $data['gtin']]]);

        $product = Product::find(sku: $data['gtin']);
        $product->bind($data);

        if ((empty($data['type']) && $this->isVariation(product: $data) || (!empty($data['type']) && $data['type'] === 'variation'))) {
            $parent = $this->getParent($data);
            $product->addParent($parent);
        }

        if (!vi()->isSimulating()) {
            $this->processed++;
            return;
        }

        if ($product->exists()) {
            $product->update();
            $this->updated++;
        } else {
            $product->save();
            $this->added++;
        }

        $this->processed++;
    }

    protected function processVariable(array $data): void
    {
        $data = [
            ...$data,
            'gtin' => "M{$data['gtin']}",
            'type' => 'variable',
            ...$this->getAttributes($data),
        ];

        $product = new Product(['meta' => ['key' => '_sku', 'value' => $data['gtin']]]);
        $product->bind($data);

        if (!vi()->isSimulating()) {
            $product->save();
        }

        $this->parents->put($product->mtacId(), $product);
    }

    protected function isVariable(array $product): bool
    {
        return $product['id'] === $product['item_group_id']
            && $this->products->filter(fn (array $p): bool => !empty($p['item_group_id']) && $p['item_group_id'] === $product['id'])->count() > 1;
    }

    protected function isVariation(array $product): bool
    {
        return $this->products->filter(fn(array $p): bool => !empty($p['item_group_id']) && $p['item_group_id'] === $product['item_group_id'])->count() > 1;
    }

    protected function slice(int $page, int $perPage): Collection
    {
        return $this->products->chunk($perPage)->get($page - 1, vi_collect());
    }

    protected function getAttributes(array $product): array
    {
        $attributes = [
            'color' => [],
            'size' => [],
        ];

        $this->products
            ->filter(fn(array $p): bool => !empty($p['item_group_id']) && $p['item_group_id'] === $product['id'])
            ->each(closure: function(array $p) use (&$attributes): void {
                if (!empty($p['color'])) {
                    $attributes['color'][] = $p['color'];
                }

                if (!empty($p['size'])) {
                    $attributes['size'][] = $p['size'];
                }
            });

        return $attributes;
    }

    protected function getCategories(): Collection
    {
        return vi()->make(key: CategoryRepository::class)->all();
    }

    protected function getParent(array $product): ?Product
    {
        $parent =  $this->parents->get($product['item_group_id']);

        if (empty($parent)) {
            $parent =  new Product(['meta' => ['key' => '_sku', 'value' => "M{$product['gtin']}"]]);
            $this->parents->put($product['item_group_id'], $parent);
        }

        return $parent;
    }
}
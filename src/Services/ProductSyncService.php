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
    // private int $added = 0;
    // private int $updated = 0;
    private int $deleted = 0;
    private int $processed = 0;

    private Collection $products;

    private Collection $parents;

    public function __construct(
        private ProductService $service,
    ) {
        // add_filter('vdhub/media/service', fn (): string => MediaService::class);
    }

    public function syncProducts(int $page = 1, int $perPage = 100): array
    {
        $this->parents = new Collection();

        $this->products = $this->service->get();
        Cache::put('woocommerce_categories', $this->getCategories());

        $this->slice($page, $perPage)
            ->each(function (array $product) {
                $this->process($product);
            });

        return [
            'processed' => $this->processed,
            'total' => $this->products->count(),
        ];
    }

    private function process(array $data): void
    {
        if (empty($data['type']) && $this->isVariable($data)) {
            $this->process([
                ...$data,
                'gtin' => "M{$data['gtin']}",
                'type' => 'variable',
                ...$this->getAttributes($data),
            ]);
        }

        $product = new Product(['meta' => ['key' => '_sku', 'value' => $data['gtin']]]);
        $product->bind($data);

        if (empty($data['type']) && $this->isVariation($data)) {
            $parent = $this->getParent($data);
            $product->addParent($parent);
        }

        if (!vi()->isSimulating()) {
            $product->save();
        }

        $this->processed++;

        if ($product->type() === 'variable') {
            $this->parents->put($product->mtacId(), $product);
        }
    }

    private function isVariable(array $product): bool
    {
        return $product['id'] === $product['item_group_id']
            && $this->products->filter(fn (array $p): bool => !empty($p['item_group_id']) && $p['item_group_id'] === $product['id'])->count() > 1;
    }

    private function isVariation(array $product): bool
    {
        return $this->products->filter(fn(array $p): bool => !empty($p['item_group_id']) && $p['item_group_id'] === $product['item_group_id'])->count() > 1;
    }

    private function slice(int $page, int $perPage): Collection
    {
        return $this->products->chunk($perPage)->get($page - 1, vi_collect());
    }

    private function getAttributes(array $product): array
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

    private function getCategories(): Collection
    {
        return vi()->make(key: CategoryRepository::class)->all();
    }

    private function getParent(array $product): ?Product
    {
        $parent =  $this->parents->get($product['item_group_id']);

        if (empty($parent)) {
            $parent =  new Product(['meta' => ['key' => '_sku', 'value' => $product['gtin']]]);
            $this->parents->put($product['item_group_id'], $parent);
        }

        return $parent;
    }
}
<?php

declare(strict_types=1);

namespace Seeru\Mtac\Services;

use Seeru\Mtac\Models\Product;
use Vdisain\Plugins\Interfaces\Support\Cache\Cache;
use Vdisain\Plugins\Interfaces\Support\Logger;

defined('ABSPATH') or die;

class SingleProductSyncService extends ProductSyncService
{
    public function syncProduct(string $sku): array
    {
        $this->boot();

        $product = $this->service->find(sku: $sku);

        if (!$product) {
            return ['processed' => 0, 'total' => 0];
        }

        $this->products->push($product);

        if ($this->isVariable($product)) {
            Logger::describe('Product is variable product, including variations');

            $this->products = $this->products->merge(
                $this->service->get()->filter(
                    fn (array $p): bool =>
                        !empty($p['item_group_id'])
                        && $p['item_group_id'] === $product['id']
                        && $p['id'] !== $product['id']
                )
            );
        }

        Cache::put('woocommerce_categories', $this->getCategories());

        $this->products->each(fn (array $product) => $this->process($product));
        $this->parents->each(fn (Product $product) => $product->sync());

        return [
            'processed' => $this->processed,
            'total' => $this->products->count(),
        ];
    }

    protected function isVariable(array $product): bool
    {
        return $product['id'] === $product['item_group_id']
            && $this->service->get()
                ->filter(fn(array $p): bool => !empty ($p['item_group_id']) && $p['item_group_id'] === $product['id'])
                ->count() > 1;
    }

    protected function isVariation(array $product): bool
    {
        return $this->service->get()
            ->filter(fn(array $p): bool => !empty ($p['item_group_id']) && $p['item_group_id'] === $product['item_group_id'])
            ->count() > 1;
    }
}
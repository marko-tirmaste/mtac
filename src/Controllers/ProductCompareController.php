<?php
/**
 * Controller class for comparing M-Tac and WooCommerce products.
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Seeru\Mtac\Controllers;
use Vdisain\Plugins\Interfaces\Support\Collection;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Repositories\ProductRepository;
use Seeru\Mtac\Services\ProductService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller class for comparing M-Tac and WooCommerce products.
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0
 */
class ProductCompareController
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductService $productService
    ) {
        //
    }

    protected Collection $wooProducts;
    protected Collection $mtacProducts;

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $this->wooProducts = $this->productRepository->allMtacProducts();
        $this->mtacProducts = $this->productService->get();

        $map = $this->mtacProducts->map(function (array $mtacProduct): array {
            $wooProduct = $this->wooProducts
                ->filter(fn(object $product): bool => (string) $product->mtac_id === (string) $mtacProduct['id'])
                ->first();

            $parentMtacProduct = !empty($mtacProduct['item_group_id']) 
                ? $this->mtacProducts
                    ->filter(fn(array $product): bool => (string) $product['id'] === (string) $mtacProduct['item_group_id'])
                    ->first()
                : null;

            return [
                'id' => $wooProduct->id ?? null,
                'mtac_id' => $mtacProduct['id'],
                // 'type' => $wooProduct->type ?? (!empty($mtacProduct['item_group_id']) ? 'Variation' : 'Simple/Variable'),
                ...(!empty($mtacProduct['item_group_id']) ? ['parent' => [
                    'id' => $wooProduct->parent_id ?? null,
                    'mtac_id' => $mtacProduct['item_group_id'] ?? null,
                    'title' => $parentMtacProduct['title'] ?? 'Not found',
                ]] : []),
                'title' => $wooProduct->title ?? $mtacProduct['title'],
                'status' => $wooProduct->status ?? null,
                '_links' => [
                    'import' => rest_url("vdisain-interfaces/mtac/product/{$mtacProduct['id']}/import"),
                    'update' => !empty($wooProduct->id) ? rest_url("vdisain-interfaces/mtac/product/{$wooProduct->id}/update") : null,
                ],
            ];
        });

        $this->wooProducts->each(function (object $wooProduct) use ($map): void {
            if ($map->where('id', $wooProduct->id)->isEmpty()) {
                $map->push([
                    'id' => $wooProduct->id,
                    'sku' => $wooProduct->mtac_id,
                    // 'type' => $wooProduct->type,
                    // 'parent_id' => $wooProduct->parent_id,
                    'title' => $wooProduct->title,
                    'status' => $wooProduct->status,
                    '_links' => [
                        'import' => rest_url("vdisain-interfaces/mtac/product/{$wooProduct->mtac_id}/import"),
                        'update' => rest_url("vdisain-interfaces/mtac/product/{$wooProduct->id}/update"),
                    ],
                ]);
            }
        });

        if ($request->has_param('diff')) {
            $map = $map->filter(fn(array $product): bool => empty($product['id']) || empty($product['mtac_id']));
        }

        return new WP_REST_Response([
            'products' => $map->values(),
            'total' => $map->count(),
        ]);
    }
}
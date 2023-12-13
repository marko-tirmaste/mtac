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

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $wooProducts = $this->productRepository->allMtacProducts();
        $mtacProducts = $this->productService->get();

        $map = $mtacProducts->map(function (array $mtacProduct) use ($wooProducts): array {
            $wooProduct = $wooProducts
                ->filter(fn(object $wooProduct): bool => (string) $wooProduct->mtac_id === (string) $mtacProduct['id'])
                ->first();

            return [
                'id' => $wooProduct->id ?? null,
                'mtac_id' => $mtacProduct['id'],
                'type' => $wooProduct->type ?? (!empty($mtacProduct['item_group_id']) ? 'Variation' : 'Simple/Variable'),
                'parent_id' => $wooProduct->parent_id ?? null,
                'parent_mtac_id' => $mtacProduct['item_group_id'] ?? null,
                'title' => $wooProduct->title ?? $mtacProduct['title'],
                'status' => $wooProduct->status ?? null,
                '_links' => [
                    'import' => rest_url("vdisain-interfaces/mtac/product/{$mtacProduct['id']}/import"),
                    'update' => !empty($wooProduct->id) ? rest_url("vdisain-interfaces/mtac/product/{$wooProduct->id}/update") : null,
                ],
            ];
        });

        $wooProducts->each(function (object $wooProduct) use ($map): void {
            if ($map->where('id', $wooProduct->id)->isEmpty()) {
                $map->push([
                    'id' => $wooProduct->id,
                    'sku' => $wooProduct->mtac_id,
                    'type' => $wooProduct->type,
                    'parent_id' => $wooProduct->parent_id,
                    'parent_mtac_id' => null,
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
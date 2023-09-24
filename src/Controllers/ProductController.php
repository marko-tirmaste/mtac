<?php
/**
 * Controller class for handling actions with M-Tac products
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-09
 */
namespace Seeru\Mtac\Controllers;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Mappers\ProductMapper;
use Vdisain\Plugins\Interfaces\Support\Log\Log;
use Seeru\Mtac\Services\ProductService;
use Vdisain\Plugins\Interfaces\Exceptions\NotFoundException;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;

set_time_limit(0);

/**
 * Controller class for handling actions with M-Tac products
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-09
 */
class ProductController
{
    /**
     * Initializes the controller instance
     * 
     * @param \Vdisain\Plugins\Interfaces\Repositories\ProductRepository $repo WooCommerce product repository
     * @param \Seeru\Mtac\Services\ProductService $service M-Tac product service
     */
    public function __construct(
        protected ProductRepository $repo,
        protected ProductService $service,
    ) {
        //
    }

    /**
     * Removes products removed by M-Tac
     * 
     * @return array
     */
    public function destroy(): array
    {
        $mtacIds = $this->service->get()->pluck('id');
        $products = $this->getAllProducts()
            ->filter(function (object $product) use ($mtacIds): bool {
                return !$mtacIds->contains($product->mtac_id);
            });

        if ($products->isEmpty()) {
            Logger::describe('No products to remove.');
            Log::info('No products to remove.');
            return [];
        }

        $products->each(function (object $product): void {
            $p = wc_get_product($product->product_id);
            if (empty($p)) {
                return;
            }
            $p->set_status('trash');
            $p->save();
            Logger::describe(sprintf('Removed %s.', $p->get_title()));
        });

        Log::info('Removed products', [$products->all()]);

        return [
            'removed' => $products->all(),
        ];
    }
    
    /**
     * Imports products from mtac
     * 
     * @return array<int>
     */
    public function import(): array
    {
        $now = time();
        $start = microtime(true);

        $products = $this->service->get();

        $page = isset($_GET['page']) 
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_products_next_page', 1);

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 100;

        if (vi()->isVerbose()) {
            Logger::describe('Updating products.');
            Logger::describe(sprintf('Page %s, per page %s, total %s.', $page, $perPage, $products->count()));
        }

        $this->processImport($this->groupVariations($products), $perPage, $page);

        update_option('vdisain_mtac_schedule_products_last', $now);
        update_option('vdisain_mtac_schedule_products_next_page', $page * $perPage > $products->count() ? 1 : $page + 1);

        return [
            'processed' => min($page * $perPage, $products->count()),
            'total' => $products->count(),
            'page' => $page,
            'per_page' => $perPage,
            'time' => round(microtime(true) - $start, 3),
        ];
    }

    /**
     * Imports products from mtac
     * 
     * @return array<int>
     */
    public function importAll(): array
    {
        $now = time();
        $products = $this->groupVariations($this->service->get());

        $this->processImport($products);

        update_option('vdisain_mtac_schedule_products_last', $now);
        update_option('vdisain_mtac_schedule_products_next_page', 1);

        return [
            'processed' => $products->count(),
            'total' => $products->count(),
        ];
    }

    /**
     * Imports single product
     * 
     * @param int $id mtac product id
     * 
     * @throws NotFoundException When product was not found
     */
    public function importProduct(int $id): void
    {
        Logger::describe("Importing single product with id {$id}.");

        $data = vi()->make(ProductService::class)->find($id);

        if (vi()->isVerbose(3)) {
            Logger::describe('ProductController::importProduct() $data');
            Logger::dump($data);
        }

        if (empty($data)) {
            throw new NotFoundException();
        }

        $this->processImport(vi_collect([$data]));
    }

    /**
     * Updates single product
     * 
     * @param int $id WooCommerce product ID
     * 
     * @throws NotFoundException When product was not found
     */
    public function updateProduct(int $id): void
    {
        $code = get_post_meta($id, '_mtac_id', true);

        if (empty($code)) {
            throw new NotFoundException();
        }

        $this->importProduct($code);
    }

    protected function groupVariations(Collection $products): Collection
    {
        return $products
            ->filter(function (array $product): bool {
                // Filter out simple and variable products
                return empty($product['item_group_id']) || $product['item_group_id'] === $product['id'];
            })
            ->map(function (array $product) use ($products): array {
                if (!empty($product['item_group_id'])) {
                    // Add variations to variable product
                    $product['variations'] = $products
                        ->filter(function (array $variation) use ($product): bool {
                            return !empty($variation['item_group_id']) && $variation['item_group_id'] === $product['item_group_id'];
                        })
                        ->map(function (array $variation): array {
                            if (isset($variation['status'])) {
                                $variation['status'] = $variation['status'] !== 'trash' ? 'publish' : $variation['status'];
                            }
                            return $variation;
                        });
                    $product['gtin'] = null;
                }

                return $product;
            });
    }

    /**
     * Processes the imported products
     * 
     * @param Collection $products Collection of imported products
     * @param int|null $perPage Optional. Number of products per page to process. Default 0 - no pagination
     * @param int|null $page Optional. Page to process
     */
    private function processImport(Collection $products, ?int $perPage = 0, ?int $page = 1): void
    {
        if (empty($page)) {
            $page = 1;
        }

        $from = !empty($perPage) ? ($page - 1) * $perPage : 0;
        $to = !empty($perPage) ? $from + $perPage : PHP_INT_MAX;
        $index = 0;

        $products->each(function (array $data) use (&$index, $from, $to) {
            if ($index >= $to) {
                return false;
            }

            if ($index >= $from) {
                $parentId = $this->processProductImport($data);
            }

            $index++;

            if (!empty($data['variations'])) {
                foreach ($data['variations'] as $variation) {
                    if ($index >= $to) {
                        return false;
                    }

                    if ($index >= $from) {
                        if (empty($parentId)) {
                            $parentId = $this->processProductImport($data);
                        }

                        $variation['parent_id'] = $parentId;

                        $this->processProductImport($variation);
                    }

                    $index++;
                }
            }
        });
    }

    private function processProductImport(array $data): ?int
    {
        try {
            $map = (new ProductMapper($data))->toArray();

            if (vi()->isVerbose()) {
                Logger::describe(__METHOD__ . '@' .  __LINE__ . ' $map');
                Logger::dump($map);
            }

            return $this->repo->updateOrCreate($map);
        } catch (\Throwable $error) {
            Logger::warn($error->getMessage() . ' ' . $error->getFile() . ' ' . $error->getLine());
        }

        return null;
    }

    protected function titleWithoutAttributes(array $product): string
    {
        return trim(
            str_replace(
                trim(($product['color'] ?? '') . ' ' . ($product['size'] ?? '')),
                '',
                is_array($product['title']) ? array_shift($product['title']) ?? '' : $product['title']
            )
        );
    }


    protected function getAllProducts(): Collection
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        return vi_collect(
            $wpdb->get_results(
                "SELECT 
                    `posts`.`post_title` AS `title`, `postmeta`.`post_id` AS `product_id`, `postmeta`.`meta_value` AS `mtac_id`
                FROM 
                    `{$wpdb->postmeta}` AS `postmeta`
                    LEFT JOIN `{$wpdb->posts}` AS `posts` ON `postmeta`.`post_id` = `posts`.`ID`  
                WHERE 
                    `postmeta`.`meta_key` = '_mtac_id'
                    AND `posts`.`post_status` = 'publish'
                GROUP BY 
                    `postmeta`.`post_id`;",
            )
        );
    }
}
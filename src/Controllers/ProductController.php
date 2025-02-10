<?php
/**
 * Controller class for handling actions with M-Tac products
 *
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-09
 */
namespace Seeru\Mtac\Controllers;
use Seeru\Mtac\Services\SingleProductSyncService;
use Vdisain\Plugins\Interfaces\Support\Performance\Performance;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Mappers\ProductMapper;
use Seeru\Mtac\Services\ProductSyncService;
use Vdisain\Plugins\Interfaces\Support\Log\Log;
use Seeru\Mtac\Services\ProductService;
use Vdisain\Plugins\Interfaces\Exceptions\NotFoundException;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;

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
        // Override the media service to keep gallery images
        add_filter('vdhub/media-service', fn(string $class): string => \Seeru\Mtac\Services\MediaService::class);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');

        $product = $this->groupVariations($this->service->get())
            ->filter(
                fn(array $p): bool => (string) $p['id'] === $id || (!empty($p['variations']) && $p['variations']->contains('id', $id))
            )
            ->first();

        if (empty($product)) {
            throw new NotFoundException();
        }

        return new WP_REST_Response([
            'product' => $product,
        ]);
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
        set_time_limit(3600);
        Performance::init(VDAI_PATH_LOGS . '/mtac/performance.log');
        Performance::start();

        $now = time();
        $memory = memory_get_usage();
        $start = microtime(true);

        $page = isset($_GET['page'])
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_products_next_page', 1);


        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25;

        Performance::log('Before sync');
        $report = [
            ...vi()->make(ProductSyncService::class)->syncProducts($page, $perPage),
            'page' => $page,
            'per_page' => $perPage,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'time' => round(microtime(true) - $start, 3),
            'memory' => memory_get_usage() - $memory,
        ];
        Performance::log('After sync');

        update_option('vdisain_mtac_schedule_products_last', $now);
        update_option('vdisain_mtac_schedule_products_next_page', $page * $perPage >= $report['total'] ? 1 : $page + 1);
        update_option('vdisain_mtac_schedule_products_report', [
            ...$report,
            'processed' => min(($page - 1) * $perPage + $report['processed'], $report['total']),
        ]);

        return [
            ...$report,
            'page' => $page,
            'per_page' => $perPage,
            'time' => round(microtime(true) - $start, 3),
        ];
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'products' => $this->groupVariations($this->service->get()),
        ]);
    }

    /**
     * Adds single M-Tac product to WooCommerce
     *
     * @param \WP_REST_Request $request The request object
     *
     * @return \WP_REST_Response
     */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $result = vi()->make(SingleProductSyncService::class)
            ->syncProduct((string) $request->get_param('id'));

        return new WP_REST_Response([
            ...$result,
            'logs' => Logger::array(),
        ]);
    }

    public function updateProduct(WP_REST_Request $request): WP_REST_Response
    {
        $sku = get_post_meta((int) $request->get_param('id'), '_sku', true);

        if (empty($sku)) {
            throw new NotFoundException();
        }

        $result = vi()->make(SingleProductSyncService::class)->syncProduct($sku);

        return new WP_REST_Response([
            ...$result,
            'logs' => Logger::array(),
        ]);
    }

    /**
     * Updates the M-Tac products
     *
     * @param \WP_REST_Request $request The request object
     *
     * @return \WP_REST_Response
     */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        set_time_limit(0);

        $now = time();
        $memory = memory_get_usage();
        $start = microtime(true);

        $filter = $this->getFilter($request);

        $page = $request->has_param('page')
            ? max((int) $request->get_param('page'), 1)
            : ($this->filterIsEmpty($filter) ? (int) get_option('vdisain_mtac_schedule_products_next_page', 1) : 1);
        $perPage = $request->has_param('per_page') ? (int) $request->get_param('per_page') : 25;

        $report = [
            ...array_filter($filter),
            ...vi()->make(ProductSyncService::class)->syncProducts($page, $perPage),
            'page' => $page,
            'per_page' => $perPage,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'time' => round(microtime(true) - $start, 3),
            'memory' => memory_get_usage() - $memory,
        ];

        if ($this->filterIsEmpty($filter)) {
            update_option('vdisain_mtac_schedule_products_last', $now);
            update_option('vdisain_mtac_schedule_products_next_page', $page * $perPage >= $report['total'] ? 1 : $page + 1);
            update_option('vdisain_mtac_schedule_products_report', [
                ...$report,
                'processed' => min(($page - 1) * $perPage + $report['processed'], $report['total']),
            ]);
        }

        return new WP_REST_Response([
            ...$report,
            'log' => Logger::array(),
        ]);
    }

    protected function getFilter(WP_REST_Request $request): array
    {
        return [
            'mtac_id' => array_filter(explode(',', $request->get_param('mtac_id') ??  '')),
            'sku' => array_filter(explode(',', $request->get_param('sku') ?? '')),
        ];
    }

    protected function filterIsEmpty(array $filter): bool
    {
        return empty($filter['mtac_id']) && empty($filter['sku']);
    }

    protected function filterProducts(Collection $products, array $filter): Collection
    {
        if (!empty($filter['mtac_id'])) {
            $products = $this->filterProductsByField($products, 'id', $filter['mtac_id']);
        }

        if (!empty($filter['sku'])) {
            $products = $this->filterProductsByField($products, 'gtin', $filter['sku']);
        }

        return $products;
    }

    protected function filterProductsByField(Collection $products, string $key, array $value): Collection
    {
        return $products
            ->filter(function (array $product) use ($key, $value): bool {
                return in_array($product[$key], $value)
                    || !empty($product['variations']) && $product['variations']->contains(fn(array $variation): bool => in_array($variation[$key], $value));
            })
            ->values();
    }

    protected function countProducts(Collection $products): int
    {
        return $products->sum(function (array $product): int {
            return 1 + (!empty($product['variations']) ? $product['variations']->count() : 0);
        });
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
     * @param string $sku Product SKU
     *
     * @throws NotFoundException When product was not found
     */
    public function importProduct(string $sku): void
    {
        Logger::describe("Importing single product with id {$sku}.");

        $data = vi()->make(ProductService::class)->find($sku);

        if (vi()->isVerbose(3)) {
            Logger::describe('ProductController::importProduct() $data');
            Logger::dump($data);
        }

        if (empty($data)) {
            throw new NotFoundException();
        }

        $this->processImport(vi_collect([$data]));
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
                        })
                        ->values();

                    $product['gtin'] = null;
                }

                return $product;
            })
            ->values();
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
                if ((string) $data['id'] === '9576') {
                    file_put_contents(__DIR__ . '/dump.log', 'Page end' . PHP_EOL, FILE_APPEND);
                }
                return false;
            }

            if ((string) $data['id'] === '9576') {
                file_put_contents(__DIR__ . '/dump.log', json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL);
            }

            if ($index >= $from) {
                $parentId = $this->processProductImport($data);
                if ((string) $data['id'] === '9576') {
                    file_put_contents(__DIR__ . '/dump.log', 'Parent ID: ' . $parentId . PHP_EOL, FILE_APPEND);
                }

                file_put_contents(
                    __DIR__ . '/dump2.log',
                    sprintf('[%s][%s >> %s] %s : %s', date('Y-m-d H:i:s'), str_pad("$from", 5), str_pad("$to", 5), str_pad("$index", 5), $data['id']) . PHP_EOL,
                    FILE_APPEND
                );
            }

            $index++;

            if (!empty($data['variations'])) {
                foreach ($data['variations'] as $variation) {
                    if ($index >= $to) {
                        if ((string) $data['id'] === '9576') {
                            file_put_contents(__DIR__ . '/dump.log', 'Page end' . PHP_EOL, FILE_APPEND);
                        }
                        return false;
                    }

                    if ($index >= $from) {
                        if (empty($parentId)) {
                            $parentId = $this->processProductImport($data);
                            if ((string) $data['id'] === '9576') {
                                file_put_contents(__DIR__ . '/dump.log', 'Parent ID: ' . $parentId . PHP_EOL, FILE_APPEND);
                            }

                            file_put_contents(
                                __DIR__ . '/dump2.log',
                                sprintf('[%s][%s >> %s] %s : %s', date('Y-m-d H:i:s'), str_pad("$from", 5), str_pad("$to", 5), str_pad("$index", 5), $data['id']) . PHP_EOL,
                                FILE_APPEND
                            );
                        }

                        $variation['parent_id'] = $parentId;

                        $this->processProductImport($variation);
                        if ((string) $data['id'] === '9576') {
                            file_put_contents(__DIR__ . '/dump.log', 'Variation processed' . PHP_EOL, FILE_APPEND);
                        }

                        file_put_contents(
                            __DIR__ . '/dump2.log',
                            sprintf('[%s][%s >> %s] %s : %s', date('Y-m-d H:i:s'), str_pad("$from", 5), str_pad("$to", 5), str_pad("$index", 5), $data['id']) . PHP_EOL,
                            FILE_APPEND
                        );
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
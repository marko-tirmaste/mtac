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
use Seeru\Mtac\Services\ProductService;
use Vdisain\Plugins\Interfaces\Exceptions\NotFoundException;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;

set_time_limit(0);

ini_set('xdebug.var_display_max_depth', '-1');
ini_set('xdebug.var_display_max_children', '-1');
ini_set('xdebug.var_display_max_data', '-1');

/**
 * Controller class for handling actions with M-Tac products
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-09
 */
class ProductController
{
    /**
     * Imports products from mtac
     * 
     * @return array<int>
     */
    public function import(): array
    {
        $now = time();
        $products = $this->groupVariations((new ProductService())->get());

        $page = isset($_GET['page']) 
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_products_next_page', 1);

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 1;

        if (vi()->isVerbose()) {
            Logger::describe('Updating products.');
            Logger::describe(sprintf('Page %s, per page %s, total %s.', $page, $perPage, $products->count()));
        }

        $this->processImport($products, $perPage, $page);

        update_option('vdisain_mtac_schedule_products_last', $now);
        update_option('vdisain_mtac_schedule_products_next_page', $page * $perPage > $products->count() ? 1 : $page + 1);

        return [
            'processed' => min($page * $perPage, $products->count()),
            'total' => $products->count(),
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
        $products = $this->groupVariations((new ProductService())->get());

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

        if (vi()->isVerbose()) {
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
        return $products->groupBy(function (array $product): string {
            return $this->titleWithoutAttributes($product);
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

        //file_put_contents(__DIR__ . '/dump.json', json_encode($products, JSON_PRETTY_PRINT));

        if (empty($perPage)) {
            $products->each(function (Collection $data): void {
                $this->processProductImport($data);
            });

            return;
        }

        // Paginated, process only specified page
        $products = $products->chunk($perPage)
            ->filter(function (Collection $products, int $index) use ($page): bool {
                return $index + 1 === $page;
            })
            ->each(function (Collection $products): void {
                $products->each(function (Collection $data): void {
                    $this->processProductImport($data);
                });
            });
    }

    private function processProductImport(Collection $data): void
    {
        try {
            $product = $data->first();
            if ($data->count() > 1) {
                $product['title'] = $this->titleWithoutAttributes($product);
                $product['gtin'] = null;
                $product['variations'] = $data;
            }
            $map = (new ProductMapper($product))->toArray();

            if (vi()->isVerbose()) {
                Logger::describe('ProductController::processProductImport() $map');
                Logger::dump($map);
            }

            vi()->make(ProductRepository::class)->updateOrCreate($map);
        } catch (\Throwable $error) {
            Logger::warn($error->getMessage() . ' ' . $error->getFile() . ' ' . $error->getLine());
        }
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
}
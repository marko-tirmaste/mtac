<?php
/**
 * Controller class for handling actions with M-Tac products
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Controllers
 * @since 1.3.0 2023-05-09
 */
namespace Vdisain\Mtac\Controllers;

defined('VDAI_PATH') or die;

use Vdisain\Mtac\Mappers\ProductMapper;
use Vdisain\Mtac\Services\ProductService;
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
 * @package Vdisain\Mtac\Controllers
 * @since 1.3.0 2023-05-09
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
        $products = (new ProductService())->get();

        $page = isset($_GET['page']) 
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_products_next_page', 1);

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 100;

        if (vi()->isVerbose()) {
            Logger::describe('Updateing products.');
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
        $products = (new ProductService())->get();

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

        if (empty($perPage)) {
            $products->each(function (array $data): void {
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
                $products->each(function (array $data): void {
                    $this->processProductImport($data);
                });
            });
    }

    private function processProductImport(array $data): void
    {
        try {
            $map = (new ProductMapper($data))->toArray();

            if (vi()->isVerbose()) {
                Logger::describe('ProductController::processProductImport() $map');
                Logger::dump($map);
            }

            //vi()->make(ProductRepository::class)->updateOrCreate($map);
        } catch (\Throwable $error) {
            Logger::warn($error->getMessage() . ' ' . $error->getFile() . ' ' . $error->getLine());
        }
    }
}
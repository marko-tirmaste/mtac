<?php
/**
 * Controller class for handling actions with M-Tac stock levels
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-07-28
 */
namespace Seeru\Mtac\Controllers;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Services\ProductService;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Exceptions\NotFoundException;
use Vdisain\Plugins\Interfaces\Repositories\ProductRepository;
use Vdisain\Plugins\Interfaces\Support\Logger;

/**
 * Controller class for handling actions with M-Tac stock levels
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-07-28
 */
class StockController
{
    /**
     * Imports stock levels from M-Tac
     * 
     * @return array<int> Results of import
     */
    public function import(): array
    {
        $now = time();
        $stocks = vi()->make(ProductService::class)->get();

        $page = isset($_GET['page'])
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_stock_next_page', 1);

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 100;

        if (vi()->isVerbose()) {
            Logger::describe('Updateing product stock levels.');
            Logger::describe(sprintf('Page %s, per page %s, total %s.', $page, $perPage, $stocks->count()));
        }

        $stocks
            ->chunk($perPage)
            ->filter(function (Collection $stocks, int $index) use ($page): bool {
                return $index + 1 === $page;
            })
            ->each(function (Collection $stocks): void {
                $stocks->each(function (array $stock): void {
                    $this->update(vi_collect($stock['sizes']['size']));
                });
            });

        update_option('vdisain_mtac_schedule_stock_last', $now);
        update_option('vdisain_mtac_schedule_stocks_next_page', $page * $perPage > $stocks->count() ? 1 : $page + 1);

        return [
            'processed' => min($page * $perPage, $stocks->count()),
            'total' => $stocks->count(),
        ];
    }

    /**
     * Imports all stock levels from mtac
     * 
     * @return array<int> Results of import
     */
    public function importAll(): array
    {
        Logger::describe("Updating stock levels for all products.");
        $now = time();

        $stocks = vi()->make(ProductService::class)->get();

        $stocks->each(function (array $stock): void {
            $this->update(vi_collect($stock['sizes']['size']));
        });

        update_option('vdisain_mtac_schedule_stock_last', $now);
        update_option('vdisain_mtac_schedule_stocks_next_page', 1);

        return [
            'processed' => $stocks->count(),
            'total' => $stocks->count(),
        ];
    }

    /**
     * Imports stock level from mtac for single product
     * 
     * @param int $id mtac product id
     * 
     * @throws NotFoundException When there are no stock levels for the product
     */
    public function importStock(int $id): void
    {
        $data = vi()->make(ProductService::class)->find($id);

        if (empty($data)) {
            throw new NotFoundException(sprintf(__('Stock levels for %s not found!', 'seeru-mtac'), $id));
        }

        $this->update(vi_collect($data['sizes']['size']));
    }

    /**
     * Imports stock level from mtac for single product by WooCommerce product ID
     * 
     * @param int $productId WooCommerce product ID
     */
    public function updateStock(int $productId): void
    {
        $this->importStock((int) get_post_meta($productId, '_mtac_id', true));
    }

    /**
     * Updates product stock quantity
     * 
     * @param \Vdisain\Plugins\Interfaces\Support\Collection $data Stock data
     */
    protected function update(Collection $data) 
    {
        $data->each(function (array $data) {
            $product = vi()->make(ProductRepository::class)->find2((int) $data['id_size'], '_mtac_id');

            if (empty($product)) {
                Logger::warn(sprintf('Product not found for %s.', $data['id_size']));
                return;
            }

            $product->set_stock_quantity($data['quantity']);
            $product->save();

            if (vi()->isVerbose()) {
                Logger::describe(sprintf(
                    'Updating %s (%s/%s) stock levels to %s.', 
                    $product->get_name(), 
                    $data['id_size'],
                    $product->get_id(), 
                    $data['quantity']
                ));
            }
        });
    }
}
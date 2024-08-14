<?php
/**
 * Controller class for handling actions with M-Tac categories
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0
 */
namespace Seeru\Mtac\Controllers;

defined('VDAI_PATH') or die;

use Seeru\Mtac\Mappers\CategoryMapper;
use Seeru\Mtac\Services\ProductService;
use Vdisain\Plugins\Interfaces\Repositories\CategoryRepository;
use Vdisain\Plugins\Interfaces\Support\Arr;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;

/**
 * Controller class for handling actions with M-Tac categories
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0
 */
class CategoryController
{
    public function import(): array
    {
        $now = time();
        $categories = $this->getCategories();

        /* $page = isset($_GET['page'])
            ? max((int) $_GET['page'], 1)
            : (int) get_option('vdisain_mtac_schedule_categories_next_page', 1);

        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 100; */

        if (vi()->isVerbose()) {
            Logger::describe('Updating categories.');
            //Logger::describe(sprintf('Page %s, per page %s, total %s.', $page, $perPage, $categories->count()));
        }

        if (vi()->isVerbose(2)) {
            Logger::describe(__METHOD__ . '@' . __LINE__ . ' $categories');
            Logger::dump($categories->toArray());
        }

        /** @var \Vdisain\Plugins\Interfaces\Repositories\CategoryRepository */
        $repo = vi()->make(CategoryRepository::class);

        $categories->each(function (array $category) use ($repo): void {
            $repo->createOrUpdate($category);
        });

        update_option('vdisain_mtac_schedule_categories_last', $now);
        //update_option('vdisain_mtac_schedule_categories_next_page', $page * $perPage > $categories->count() ? 1 : $page + 1);

        return [
            //'processed' => min($page * $perPage, $categories->count()),
            'total' => $categories->count(),
        ];
    }

    protected function getCategories(): Collection
    {
        $categories = [];

        vi()->make(ProductService::class)
            ->get()
            ->pluck('product_type')
            ->unique()
            ->each(function (string $id) use (&$categories): void {
                $path = explode(' > ', $id);
                Arr::set($categories, $path, $path[count($path) - 1]);
            });

        return vi_collect($categories)
            ->mapWithKeys(function (array $children, string $name): array {
                return [
                    $name => (new CategoryMapper(['name' => $name, 'children' => $children]))->toArray(),
                ];
            });
    }
}
<?php
/**
 * View class for M-Tac dashboard page
 *
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Views
 */

namespace Seeru\Mtac\Views;

use Seeru\Mtac\DataTransferObjects\CacheReport;
use Seeru\Mtac\DataTransferObjects\ProductSyncReport;
use Vdisain\Plugins\Interfaces\Views\View;

defined('VDAI_PATH') or die;

/**
 * View class for M-Tac dashboard page
 *
 * @package Seeru\Mtac\Views
 */
class DashboardView extends View
{
    /**
     * Initializes the view
     */
    public function __construct()
    {
        parent::__construct();

        $cacheExists = file_exists(VDAI_PATH_CACHE_MTAC . '/products.json');

        $this->cache = new CacheReport(
            $cacheExists,
            $cacheExists ? date('Y-m-d H:i:s', filemtime(VDAI_PATH_CACHE_MTAC . '/products.json')) : __('Never', 'seeru-mtac'),
            filesize(VDAI_PATH_CACHE_MTAC . '/products.json') ?? 0,
        );

        $this->products = ProductSyncReport::fromConfig('vdisain_mtac_schedule_products_report');
    }

    public CacheReport $cache;
    public ProductSyncReport $products;

    /**
     * Template name
     * @var string|null
     */
    protected ?string $name = 'mtac';

    /**
     * Register admin menu link and page
     */
    public static function register(): void
    {
        if (empty(vi_config('common.module.mtac'))) {
            return;
        }

        add_submenu_page(
            'vdisain-interfaces-dashboard',
            __('M-Tac', 'seeru-mtac'),
            __('M-Tac', 'seeru-mtac'),
            'manage_options',
            'seeru-mtac',
            function (): void {
                echo (new static())->render('dashboard', VDAI_PATH_PACKAGES . '/mtac/resources');
            },
        );
    }
}
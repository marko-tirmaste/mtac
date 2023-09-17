<?php
/**
 * View class for mtac admin page
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Views
 * @since 1.0.0 2023-05-04
 */
namespace Seeru\Mtac\Views;

defined('VDAI_PATH') or die;

use Vdisain\Plugins\Interfaces\Repositories\CategoryRepository;
use Vdisain\Plugins\Interfaces\Interfaces;
use Vdisain\Plugins\Interfaces\Views\View;
use Vdisain\Plugins\Interfaces\Models\Settings;

/**
 * View class for mtac admin page
 * 
 * @package Seeru\Mtac\Views
 * @since 1.0.0 2023-05-04
 */
class AdminView extends View
{
    /**
     * Initializes the view
     */
    public function __construct()
    {
        parent::__construct();

        $this->settings = Interfaces::instance()->settings();

        $this->categories = vi()
            ->make(CategoryRepository::class)
            ->all()
            ->map(function (\WP_Term $term): array {
                return [
                    'value' => $term->term_id,
                    'label' => $term->name,
                ];
            })
            ->all();

        $this->schedules = vi_collect(apply_filters('cron_schedules', []))
            ->filter(function (array $schedule, string $key): bool {
                return strpos($key, 'vi_') !== false;
            })
            ->mapWithKeys(function (array $schedule, string $key): array {
                return [$key => $schedule['display']];
            })
            ->all();
    }

    /**
     * Settings
     * @var Settings
     */
    protected Settings $settings;
    /**
     * Template name
     * @var string|null
     */
    protected ?string $name = 'mtac';

    protected array $categories;
    /**
     * Cron schedules
     * 
     * @var array
     */
    protected array $schedules;

    /**
     * Register admin menu link and page
     */
    public static function register(): void
    {
        if (empty(vi()->settings()->common['module']['mtac'])) {
            return;
        }

        add_submenu_page(
            'vdisain-interfaces-dashboard',
            __('M-Tac', 'seeru-mtac'),
            __('M-Tac', 'seeru-mtac'),
            'manage_options',
            'seeru-mtac',
            function (): void {
                echo (new static())->render('settings', VDAI_PATH_PACKAGES . '/mtac/resources');
            },
        );
    }
}
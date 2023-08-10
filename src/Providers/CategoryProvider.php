<?php
/**
 * Provider class for package product category actions and filters registrations
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Providers
 * @since 1.3.0 2023-05-17
 */
namespace Vdisain\Mtac\Providers;

defined('VDAI_PATH') or die;

use Vdisain\Mtac\Controllers\Taxonomy\CategoryController;

/**
 * Provider class for package product category actions and filters registrations
 * 
 * @package Vdisain\Mtac\Providers
 * @since 1.3.0 2023-05-17
 */
class CategoryProvider
{
    /**
     * Registers actions and filters
     */
    public function register(): void
    {
        add_action('product_cat_add_form_fields', [vi()->make(CategoryController::class), 'create']);
        add_action('product_cat_edit_form_fields', [vi()->make(CategoryController::class), 'edit'], 10, 2);

        add_action('created_product_cat', [vi()->make(CategoryController::class), 'store']);
        add_action('edited_product_cat', [vi()->make(CategoryController::class), 'store']);

        add_filter('manage_edit-product_cat_columns', [vi()->make(CategoryController::class), 'columns']);

        add_filter('manage_product_cat_custom_column', [vi()->make(CategoryController::class), 'columnContent'], 10, 3);
    }
}
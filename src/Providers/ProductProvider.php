<?php
/**
 * Provider class for package product actions and filters registrations
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Providers
 * @since 0.0.1 2023-06-09
 */
namespace Vdisain\Mtac\Providers;

defined('VDAI_PATH') or die;

/**
 * Provider class for package product actions and filters registrations
 * 
 * @package Vdisain\Mtac\Providers
 * @since 0.0.1 2023-06-09
 */
class ProductProvider
{
    /**
     * Registers actions and filters
     */
    public function register(): void
    {
        add_filter('woocommerce_product_data_store_cpt_get_products_query', function (array $query, array $queryVars): array {
            if (empty($queryVars['mtac_id'])) {
                return $query;
            }

            $mtacId = esc_attr($queryVars['mtac_id']);
            
            $query['meta_query'][] = [
                'key' => '_mtac_id',
                'value' => $mtacId,
                'compare' => is_array($mtacId) ? 'IN' : '=',
            ];

            return $query;
        }, 10, 2);
    }
}
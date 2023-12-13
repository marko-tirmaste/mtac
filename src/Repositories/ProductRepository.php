<?php
/**
 * Repository class for handling WooCommerce product records
 * 
 * @author Web Design Agency <info@vdisain.ee>
 * @package Seeru\Mtac\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Seeru\Mtac\Repositories;

use Vdisain\Plugins\Interfaces\Repositories\ProductRepository as BaseProductRepository;
use Vdisain\Plugins\Interfaces\Support\Collection;

/**
 * Repository class for handling WooCommerce product records
 * 
 * @package Seeru\Mtac\Repositories
 * @since 1.0.0
 */
class ProductRepository extends BaseProductRepository
{
    /**
     * Gets all M-Tac products in WooCommerce.
     * 
     * @return \Vdisain\Plugins\Interfaces\Support\Collection
     */
    public function allMtacProducts(): Collection
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        return vi_collect(
            $wpdb->get_results(
                "SELECT 
                    `{$wpdb->postmeta}`.`post_id` AS `id`,
                    `{$wpdb->postmeta}`.`meta_value` AS `mtac_id`,
                    `{$wpdb->posts}`.`post_title` AS `title`,
                    `{$wpdb->posts}`.`post_status` AS `status`,
                    `{$wpdb->posts}`.`post_type` AS `type`,
                    `{$wpdb->posts}`.`post_parent` AS `parent_id`
                FROM 
                    `{$wpdb->postmeta}` 
                    LEFT JOIN `{$wpdb->posts}` ON `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
                WHERE 
                    `{$wpdb->postmeta}`.`meta_key` = '_mtac_id';"
            )
        );
    }

    /**
     * Gets all M-Tac products in WooCommerce that are not trashed.
     * 
     * @return \Vdisain\Plugins\Interfaces\Support\Collection
     */
    public function allMtacProductsNotTrashed(): Collection
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        return vi_collect(
            $wpdb->get_results(
                "SELECT 
                    `{$wpdb->postmeta}`.`post_id` AS `id`,
                    `{$wpdb->postmeta}`.`meta_value` AS `mtac_id`,
                    `{$wpdb->posts}`.`post_title` AS `title`
                FROM 
                    `{$wpdb->postmeta}` 
                    LEFT JOIN `{$wpdb->posts}` ON `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
                WHERE 
                    `{$wpdb->postmeta}`.`meta_key` = '_mtac_id'
                    AND `{$wpdb->posts}`.`post_status` != 'trash';"
            )
        );
    }

    /**
     * Gets all M-Tac products in WooCommerce that are not out of stock.
     * 
     * @return \Vdisain\Plugins\Interfaces\Support\Collection
     */
    public function allMtacProductsNotOutOfStock(): Collection
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        return vi_collect(
            $wpdb->get_results(
                "SELECT 
                    `mtac`.`post_id` AS `id`,
                    `mtac`.`meta_value` AS `mtac_id`,
                    `{$wpdb->posts}`.`post_title` AS `title`
                FROM 
                    `{$wpdb->postmeta}` AS `mtac` 
                    LEFT JOIN `{$wpdb->posts}` ON `mtac`.`post_id` = `{$wpdb->posts}`.`ID`
                    LEFT JOIN `{$wpdb->postmeta}` AS `stock` ON `mtac`.`post_id` = `stock`.`post_id` AND `stock`.`meta_key` = '_stock'
                WHERE 
                    `mtac`.`meta_key` = '_mtac_id'
                    AND `stock`.`meta_value` > 0
                    AND `{$wpdb->posts}`.`post_status` != 'trash';"
            )
        );
    }
}
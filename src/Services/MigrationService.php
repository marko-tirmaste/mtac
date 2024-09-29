<?php

declare(strict_types=1);

namespace Seeru\Mtac\Services;
use Vdisain\Plugins\Interfaces\Support\Collection;
use Vdisain\Plugins\Interfaces\Support\Logger;

class MigrationService
{
    public function updateVariableProductSKUs(): void
    {
        $this->getVariables()
            ->filter(fn (object $product) => empty($product->sku) || substr($product->sku, 0, 1) !== 'M')
            ->each(function (object $product) {
                $this->updateVariableProductSKU($product);
            });
    }

    public function updateAttachmentsMeta(): void
    {
        $this->getProductsWithAttachments()
            ->each(function (object $product): void {
                if (!empty($product->thumbnail_id) && !empty($product->image_url)) {
                    $this->updateAttachmentMeta((int) $product->thumbnail_id, $product->image_url);
                    delete_post_meta($product->id, '_vdai_downloaded_image');
                }

                if (!empty($product->gallery) && !empty($product->gallery_urls)) {
                    $urls = unserialize($product->gallery_urls);

                    foreach (array_filter(explode(',', $product->gallery)) as $index => $id) {
                        if (!empty($urls[$index])) {
                            $this->updateAttachmentMeta((int) $id, $urls[$index]);
                        }
                    }

                    delete_post_meta($product->id, '_vdai_downloaded_gallery');
                }
            });
    }

    private function updateAttachmentMeta(int $id, string $url): void
    {
        update_post_meta($id, '_mtac_id', $url);
        update_post_meta($id, '_vdai_original', $url);
    }

    private function updateVariableProductSKU(object $product): void
    {
        if (empty($product->sku)) {
            Logger::warn("Product {$product->title} ({$product->id}) has no SKU");
            return;
        }

        update_post_meta($product->id, '_sku', "M{$product->sku}");
        update_post_meta($product->id, '_mtac_id', "M{$product->sku}");

        Logger::describe("Product {$product->title} ({$product->id}) SKU updated to M{$product->sku}");
    }

    private function getProductsWithAttachments(): Collection
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        return new Collection($wpdb->get_results(
            "SELECT
                p.ID AS id,
                p.post_title AS title,
                pmi.meta_value AS thumbnail_id,
                pmg.meta_value AS gallery,
                pmdi.meta_value AS image_url,
                pmdg.meta_value AS gallery_urls
            FROM 
                {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} AS pmi ON p.ID = pmi.post_id AND pmi.meta_key = '_thumbnail_id'
                LEFT JOIN {$wpdb->postmeta} AS pmdi ON p.ID = pmdi.post_id AND pmdi.meta_key = '_vdai_downloaded_image'
                LEFT JOIN {$wpdb->postmeta} AS pmdg ON p.ID = pmdg.post_id AND pmdg.meta_key = '_vdai_downloaded_gallery'
                LEFT JOIN {$wpdb->postmeta} AS pmg ON p.ID = pmg.post_id AND pmg.meta_key = '_product_image_gallery'
            WHERE
                pmdi.meta_value IS NOT NULL;
            "
        ));
    }

    private function getVariables(): Collection
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $taxonomyId = $this->getVariableTermTaxonomyId();

        return new Collection($wpdb->get_results(
            "SELECT 
                p.ID AS id,
                p.post_title AS title,
                pms.meta_value AS sku,
                pmi.meta_value AS mtac_id
            FROM 
                {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} AS pms ON p.ID = pms.post_id AND pms.meta_key = '_sku'
                LEFT JOIN {$wpdb->postmeta} AS pmi ON p.ID = pmi.post_id AND pmi.meta_key = '_mtac_id'
            WHERE 
                EXISTS (
                    SELECT 
                        1
                    FROM 
                        {$wpdb->term_relationships} AS tr
                    WHERE 
                        tr.object_id = p.ID
                        AND tr.term_taxonomy_id = {$taxonomyId}
                );"
            // LIMIT 10000;"
        ));
    }

    private function getVariableTermTaxonomyId(): int
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        return (int) $wpdb->get_var(
            "SELECT 
                tt.term_taxonomy_id
            FROM 
                {$wpdb->term_taxonomy} AS tt
            WHERE 
                EXISTS (
                    SELECT 
                        1
                    FROM 
                        {$wpdb->terms} AS t
                    WHERE 
                        t.term_id = tt.term_id
                        AND t.slug = 'variable'
                );"
        );
    }
}
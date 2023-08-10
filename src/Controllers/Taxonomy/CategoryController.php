<?php
/**
 * Taxonomy controller class for the product category
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Dirceto\Controllers\Taxonomy
 * @since 0.0.1 2023-06-05
 */

namespace Vdisain\mtac\Controllers\Taxonomy;

defined('ABSPATH') or exit;

/**
 * Taxonomy controller class for the product category
 * 
 * @package Vdisain\Dirceto\Controllers\Taxonomy
 * @since 0.0.1 2023-06-05
 */
class CategoryController
{
    /**
     * Displaies fields for creating new category
     * 
     * @param string $taxonomy
     */
    public function create(string $taxonomy): void 
    {
?>

<div class="form-field">
    <label for="mtac_id"><?= __('mtac code', 'vdisain-mtac') ?></label>
    <input type="text" name="mtac_id">
    <p><?= __('Class code from mtac for product category mapping when importing products.', 'vdisain-mtac') ?></p>
</div>

<?php
    }

    /**
     * Gets custom columns for product category list
     * 
     * @param array $columns Existing columns
     * 
     * @return array
     */
    public function columns(array $columns): array 
    {
        $columns['mtac_id'] = __('mtac code', 'vdisain-mtac');
        return $columns;
    }

    /**
     * Gets content for custom columns
     * 
     * @param string $content Column content
     * @param string $name Column name
     * @param int $termId Term ID
     * 
     * @return string
     */
    public function columnContent(string $content, string $name, int $termId): string
    {
        switch ($name) {
            case 'mtac_id':
                $content .= get_term_meta($termId, '_mtac_id', true);
                break;

            default:
                break;
        }

        return $content;
    }

    /**
     * Displaies fields for editing existing category
     * 
     * @param \WP_Term $term Category
     * @param string $taxonomy Category taxonomy
     */
    public function edit(\WP_Term $term, string $taxonomy): void
    {
?>

<tr class="form-field">
    <th scope="row" valign="top">
        <label for="mtac_id">
            <?= __('mtac code', 'vdisain-sleepwell'); ?>
        </label>
    </th>
    <td>
        <input type="text" name="mtac_id" id="mtac_id" value="<?= esc_attr(get_term_meta($term->term_id, '_mtac_id', true)) ?>">
        <p><?= __('Class code from mtac for product category mapping when importing products.', 'vdisain-mtac') ?></p>
    </td>
</tr>

<?php
    }

    /**
     * Stores category data
     * 
     * @param int $termId Category record ID
     */
    public function store(int $termId): void
    {
        if (isset($_POST['mtac_id'])) {
            update_term_meta($termId, '_mtac_id', $_POST['mtac_id']);
        }
    }
}
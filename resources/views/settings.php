<?php
/**
 * View template for M-Tac options
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 */
defined('ABSPATH') or die;

/** @var \Seeru\Mtac\Views\AdminView $this */

?>

<?php include 'partials/header.php' ?>

<?php include 'partials/form-header.php' ?>

<div class="grid grid-cols-1 md:grid-cols-[3fr_1fr] gap-4 lg:gap-8 mb-4">
    <div>
        <h3 class="mb-6 font-semibold text-lg leading-tight dark:text-white">
            <?= __('XML Feed', 'vdisain-interfaces') ?>
        </h3>

        <vd-field 
            name="vdai_mtac_options[xml_url]"
            value="<?= vi_config('mtac.xml_url') ?>" 
        >
            <?= __('XML Feed Url', 'seeru-mtac') ?>
        </vd-field>
    </div>

    <div class="grid grid-cols-1 place-content-start p-4 bg-gray-200 dark:bg-gray-800 rounded dark:text-white">
        <p class="mb-6 text-sm text-black/70 dark:text-gray-100">
            <?= __('This are settings for mtac XML Feed.', 'vdisain-interfaces') ?>
        </p>
    </div>
</div>

<hr class="h-px my-8 bg-gray-200 border-0">

<div class="grid grid-cols-1 md:grid-cols-[3fr_1fr] gap-4 lg:gap-8 mb-4">
    <div>
        <h3 class="mb-6 font-semibold text-lg leading-tight dark:text-white">
            <?= __('Product Sync', 'vdisain-interfaces') ?>
        </h3>

        <vd-field name="vdai_mtac_options[markup]" value="<?= vi_config('mtac.markup') ?>">
            <?= __('Add price markup %', 'seeru-mtac') ?>
        </vd-field>

        <vd-field name="vdai_mtac_options[vat]" value="<?= vi_config('mtac.vat') ?>">
            <?= __('Add VAT %', 'seeru-mtac') ?>
        </vd-field>

        <vd-select
            default="<?= __('Disabled', 'seeru-mtac') ?>"
            name="vdai_mtac_options[schedule][method]"
            :options="<?= htmlentities(json_encode([
                'wp' => __('WP Cron', 'seeru-mtac'),
                'url' => __('URL', 'seeru-mtac')
            ], JSON_HEX_QUOT), ENT_QUOTES) ?>"
            value="<?= vi_config('mtac.schedule.method') ?>" 
        >
            <?= __('Cron', 'seeru-mtac') ?>
        </vd-select>

        <vd-field name="vdai_mtac_options[schedule][products][time]" value="<?= vi_config('mtac.schedule.products.time') ?>">
            <?= __('Products cron start (server time)', 'seeru-mtac') ?>
        </vd-field>

        <vd-select
            default="<?= __('Disabled', 'vdisain-interfaces') ?>"
            name="vdai_mtac_options[schedule][products][interval]"
            :options="<?= htmlentities(json_encode($this->schedules, JSON_HEX_QUOT), ENT_QUOTES) ?>"
            value="<?= vi_config('mtac.schedule.products.interval') ?>" 
        >
            <?= __('Products cron interval', 'vdisain-interfaces') ?>
        </vd-select>
    </div>

    <div class="grid grid-cols-1 place-content-start p-4 bg-gray-200 dark:bg-gray-800 rounded dark:text-white">
        <p class="mb-6 text-sm text-black/70 dark:text-gray-100">
            <?= __('Update all products.', 'seeru-mtac') ?>
        </p>

        <vi-dialog>
            <template #trigger><?= __('Import products', 'seeru-mtac') ?></template>
            <template #header><?= __('Product importing', 'seeru-mtac') ?></template>
            <vi-product-sync service="mtac"></vi-product-sync>
        </vi-dialog>

        <hr class="w-full h-px my-4 bg-gray-200 border-0">

        <p class="mb-6 text-sm text-black/70 dark:text-gray-100">
            <?= sprintf(
                __('For cronjob over URL, add command %s to the crontab.', 'seeru-mtac'),
                '<code>*/1 * * * * curl https://yourdomain.com/wp-json/vdisain-interfaces/mtac/cron</code>'
            ) ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-[3fr_1fr] gap-4 lg:gap-8 mb-4">
    <div><
        <h3 class="mb-6 font-semibold text-lg leading-tight dark:text-white">
            <?= __('Product data', 'seeru-mtac') ?>
        </h3>

        <vd-toggle 
            name="vdai_mtac_options[new_status]" 
            checked="<?= vi_config('mtac.new_status', 0) ?>" 
            :options="<?= htmlentities(json_encode([
               0 => __('Published', 'seeru-mtac'),
               'draft' => __('Draft', 'seeru-mtac'),
               'private' => __('Private', 'seeru-mtac'),
           ])) ?>"
           class="mb-4"
        >
            <?= __('New product status', 'seeru-mtac') ?>
        </vd-toggle>

        <?php $options = htmlentities(json_encode([
            0 => __('Off', 'seeru-mtac'), 
            'import' => __('Import only', 'seeru-mtac'), 
            'import-update' => __('Import and update', 'seeru-mtac')
        ])) ?>

        <vd-toggle name="vdai_mtac_options[field][name]" checked="<?= vi_config('mtac.field.name', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Name', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][description]" checked="<?= vi_config('mtac.field.description', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Description', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][price]" checked="<?= vi_config('mtac.field.price', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Price', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][attributes]" checked="<?= vi_config('mtac.field.attributes', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Attributes', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][categories]" checked="<?= vi_config('mtac.field.categories', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Categories', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][images]" checked="<?= vi_config('mtac.field.images', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Images', 'seeru-mtac') ?>
        </vd-toggle>

        <h3 class="mb-6 font-semibold text-lg leading-tight dark:text-white">
            <?= __('Category data', 'seeru-mtac') ?>
        </h3>
        
        <vd-toggle name="vdai_mtac_options[categories][field][name]" checked="<?= vi_config('mtac.categories.field.name', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Name', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[categories][field][slug]" checked="<?= vi_config('mtac.categories.field.slug', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Slug', 'seeru-mtac') ?>
        </vd-toggle>
    </div>

    <div class="grid grid-cols-1 place-content-start p-4 bg-gray-200 dark:bg-gray-800 rounded dark:text-white">
    </div>
</div>

<?php include 'partials/form-footer.php' ?>

<?php include 'partials/footer.php' ?>

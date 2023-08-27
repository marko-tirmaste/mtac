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

        <vd-field 
            name="vdai_mtac_options[markup]"
            value="<?= vi_config('mtac.markup') ?>" 
        >
            <?= __('Add price markup %', 'seeru-mtac') ?>
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

        <vd-select
            default="<?= __('Disabled', 'vdisain-interfaces') ?>"
            name="vdai_mtac_options[schedule][products]"
            :options="<?= htmlentities(json_encode($this->schedules, JSON_HEX_QUOT), ENT_QUOTES) ?>"
            value="<?= vi_config('mtac.schedule.products') ?>" 
        >
            <?= __('Products', 'vdisain-interfaces') ?>
        </vd-select>

        <vd-select
            default="<?= __('Disabled', 'vdisain-interfaces') ?>"
            name="vdai_mtac_options[schedule][stock]"
            :options="<?= htmlentities(json_encode($this->schedules, JSON_HEX_QUOT), ENT_QUOTES) ?>"
            value="<?= vi_config('mtac.schedule.stock') ?>" 
        >
            <?= __('Stock', 'vdisain-interfaces') ?>
        </vd-select>

        <vd-autocomplete
            name="vdai_mtac_options[category]"
            :options="<?= htmlentities(json_encode($this->categories, JSON_HEX_QUOT), ENT_QUOTES) ?>"
            value="<?= vi_config('mtac.category') ?>" 
        >
            <?= __('Category to import to', 'seeru-mtac') ?>
        </vd-autocomplete>
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
            <?= __('Update all stocks', 'seeru-mtac') ?>
        </p>
    
        <vi-dialog>
            <template #trigger><?= __('Update stock', 'seeru-mtac') ?></template>
            <template #header><?= __('Stock updating', 'seeru-mtac') ?></template>
            <vi-stock-sync service="mtac"></vi-stock-sync>
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
            <?= __('Data', 'seeru-mtac') ?>
        </h3>

        <?php $options = htmlentities(json_encode([
            0 => __('Off', 'seeru-mtac'), 
            'import' => __('Import only', 'seeru-mtac'), 
            'import-update' => __('Import and update', 'seeru-mtac')
        ])) ?>

        <vd-toggle name="vdai_mtac_options[field][name]" checked="<?= vi_config('mtac.field.name', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Name', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][short_description]" checked="<?= vi_config('mtac.field.short_description', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Short description', 'seeru-mtac') ?>
        </vd-toggle>

        <vd-toggle name="vdai_mtac_options[field][images]" checked="<?= vi_config('mtac.field.images', 'import-update') ?>" :options="<?= $options ?>">
            <?= __('Images', 'seeru-mtac') ?>
        </vd-toggle>
    </div>

    <div class="grid grid-cols-1 place-content-start p-4 bg-gray-200 dark:bg-gray-800 rounded dark:text-white">
    </div>
</div>

<?php include 'partials/form-footer.php' ?>

<?php include 'partials/footer.php' ?>

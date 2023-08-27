<?php
/** 
 * Template partial for the form header part
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @since 0.0.1 2023-06-22
 */

defined('VDAI_PATH') or die;

?>

<form action="options.php" method="post">
    <?= settings_fields('vdai_mtac_options') ?>

    <div class="flex justify-end mb-4">
        <button type="submit" role="button"
            class="px-4 py-2 border-none rounded bg-gray-800 focus:bg-gray-600 hover:bg-gray-600 font-semibold text-white uppercase cursor-pointer">
            <?= __('Save', 'vdisain-interfaces') ?>
        </button>
    </div>

    <hr class="h-px my-8 bg-gray-200 border-0">
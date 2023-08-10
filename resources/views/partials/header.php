<?php
/** 
 * Template partial for the page header part
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @since 0.0.1 2023-06-22
 */

defined('VDAI_PATH') or die;

$page = isset($_GET['page']) ? $_GET['page'] : null;

$menu = [
    [
        'label' => __('Settings', 'vdisain-mtac'), 
        'url' => '/wp-admin/admin.php?page=vdisain-mtac', 
        'icon' => 'gears',
        'active' => $page === 'vdisain-mtac',
    ],
];

?>

<div id="vdisain-interfaces__app">
    <vd-layout :menu="<?= htmlentities(json_encode($menu, JSON_HEX_QUOT), ENT_QUOTES) ?>">
        <template #title>
            <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white">
                <?= __('M-Tac Interface', 'vdisain-mtac') ?>
            </h2>
        </template>

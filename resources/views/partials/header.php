<?php
/**
 * Template partial for the page header part
 *
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @since 0.0.1 2023-06-22
 */

defined('VDAI_PATH') or die;

$page = isset($_GET['page']) ? $_GET['page'] : null;

$menu = [
    [
        'label' => __('Dashboard', 'seeru-mtac'),
        'url' => '/wp-admin/admin.php?page=seeru-mtac',
        'icon' => 'chart-pie',
        'active' => $page === 'seeru-mtac',
    ],
    [
        'label' => __('Settings', 'seeru-mtac'),
        'url' => '/wp-admin/admin.php?page=seeru-mtac-settings',
        'icon' => 'gears',
        'active' => $page === 'seeru-mtac-settings',
    ],
];

?>

<div id="vdisain-interfaces__app">
    <vd-layout :menu="<?= htmlentities(json_encode($menu, JSON_HEX_QUOT), ENT_QUOTES) ?>">
        <template #title>
            <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white">
                <?= __('M-Tac Interface', 'seeru-mtac') ?>
            </h2>
        </template>

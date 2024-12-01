<?php
/**
 * View template for M-Tac options
 *
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 */

use Vdisain\Plugins\Interfaces\Support\FileHandler;

defined('ABSPATH') or die;

/** @var \Seeru\Mtac\Views\DashboardView $this */

?>

<?php include 'partials/header.php' ?>

<div>
    <h3 class="mb-6 font-semibold text-lg leading-tight dark:text-white">
        M-Tac status
    </h3>

    <table class="table erply-overview-table mb-6 text-sm text-black/70 dark:text-gray-100">
        <tbody>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    XML downloaded at
                </th>
                <td><?= $this->cache->updatedAt ?></td>
            </tr>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    XML size
                </th>
                <td><?= vi()->make(FileHandler::class)->getHumanReadableSize($this->cache->size) ?></td>
            </tr>

            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    Products updated at
                </th>
                <td><?= $this->products->updatedAt ?></td>
            </tr>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    Progress
                </th>
                <td><?= $this->products->updated ?> out of <?= $this->products->total ?></td>
            </tr>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    Last execution time (s)
                </th>
                <td><?= $this->products->executionTime ?></td>
            </tr>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    Memory usage
                </th>
                <td><?= vi()->make(FileHandler::class)->getHumanReadableSize($this->products->memoryUsage) ?></td>
            </tr>
            <tr>
                <th
                    role="row"
                    class="min-w-[12rem] font-semibold text-left"
                >
                    Currently running
                </th>
                <td><?= empty(get_option('vdisain_mtac_schedule_products_running')) ? 'No' : 'Yes' ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php include 'partials/footer.php' ?>

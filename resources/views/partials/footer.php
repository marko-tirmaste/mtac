<?php
/** 
 * Template partial for the page footer part
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @since 0.0.1 2023-06-22
 */

defined('VDAI_PATH') or die;

?>

        <template #footer>
            <p class="mb-0 mt-0 text-sm text-black/70 dark:text-gray-100">
                <?= sprintf(__('Version %s, build %s', 'vdisain-interfaces'), VDAI_VERSION, VDAI_BUILD) ?>
            </p>
        </template>    
    </vd-layout>
</div>
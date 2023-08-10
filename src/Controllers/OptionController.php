<?php
/**
 * Controller class for handling actions with package Wordpress options
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Controllers
 * @since 1.3.0 2023-05-10
 */
namespace Vdisain\Mtac\Controllers;

defined('VDAI_PATH') or die;

/**
 * Controller class for handling actions with package Wordpress options
 * 
 * @package Vdisain\Mtac\Controllers
 * @since 1.3.0 2023-05-10
 */
class OptionController
{
    /**
     * Gets all package Wordpress options
     * 
     * @return array
     */
    public function all(): array
    {
        return (array) get_option('vdai_mtac_options', []);
    }

    /**
     * Register pacakge Wordpress options
     */
    public function register(): void
    {
        register_setting('vdai_mtac_options', 'vdai_mtac_options', [$this, 'validate']);
    }

    /**
     * Validates options on save
     * 
     * @param array|null $input Options data
     * 
     * @return array|null
     */
    public function validate(?array $input = []): ?array
    {
        return $input;
    }
}
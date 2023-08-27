<?php
/**
 * Controller class for handling actions with package Wordpress options
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-10
 */
namespace Seeru\Mtac\Controllers;

defined('VDAI_PATH') or die;

/**
 * Controller class for handling actions with package Wordpress options
 * 
 * @package Seeru\Mtac\Controllers
 * @since 1.0.0 2023-05-10
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
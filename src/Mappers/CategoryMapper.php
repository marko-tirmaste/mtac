<?php
/**
 * Mapper class for M-Tac category
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0
 */
namespace Seeru\Mtac\Mappers;

defined('ABSPATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\MapperContract;
use Vdisain\Plugins\Interfaces\Support\Mapper;

/**
 * Mapper class for M-Tac category
 * 
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0
 */
class CategoryMapper extends Mapper implements MapperContract 
{
    /**
     * Maps M-Tac category data to the WooCommerce category data
     * 
     * @param array $data M-Tac category data
     * 
     * @return array
     */
    public function map($data): array
    {
        $slug = sanitize_title($data['name']);

        $map = [
            'id' => $this->id($slug),
            'status' => 'publish',
            'meta' => [
                '_mtac_id' => $slug,
            ]
        ];

        $isNew = !empty($map['id']);

        if ($this->isMapping('name', $isNew)) {
            $map['name'] = ['et' => $data['name']];
        }

        if ($this->isMapping('slug', $isNew)) {
            $map['slug'] = ['et' => $slug];
        }

        if (!empty($data['children'])) {
            $map['children'] = vi_collect($data['children'] ?? [])
                ->mapWithKeys(function (array|string $children, string $name): array {
                    return [
                        sanitize_title($name) => (new static(['name' => $name, 'children' => is_array($children) ? $children : null]))->toArray(),
                    ];
                })
                ->all();
        }

        return $map;
    }

    /**
     * Checks if field should be mapped
     * 
     * @param string $field Field name
     * @param bool $isNew Is new product
     * 
     * @return bool
     */
    protected function isMapping(string $field, bool $isNew): bool
    {
        $rule = vi_config('mtac.categories.field.' . $field, 'import-update');
        return !(empty($rule) || ($rule === 'import' && !$isNew));
    }

    /**
     * Gets category ID if category exists
     * 
     * @param string $slug Category slug
     * 
     * @return int|null
     */
    protected function id(string $slug): ?int
    {
        $term = get_term_by('slug', $slug, 'product_cat');
        return $term->term_id ?? null;
    }
}
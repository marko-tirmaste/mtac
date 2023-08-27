<?php
/**
 * Mapper class for M-Tac attribute
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
 * Mapper class for M-Tac attribute
 * 
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0
 */
class BrandToAttributeMapper extends Mapper implements MapperContract, \JsonSerializable
{
    /**
     * Maps M-Tac product attribute data to WooCommerce product attribute data
     * 
     * @param string $data M-Tac product attribute data.
     * 
     * @return array
     */
    public function map($data): array
    {
        return [
            'name' => [
                'et' => 'Bränd',
            ],
            'options' => vi_collect([new AttributeOptionMapper($data)]),
            'visible' => true,
            'variation' => false,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
<?php
/**
 * Mapper class for M-Tac product attribute option
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
 * Mapper class for M-Tac product attribute option
 * 
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0
 */
class AttributeOptionMapper extends Mapper implements MapperContract, \JsonSerializable
{
    /**
     * Maps M-Tac product attribute option data to the WooCommerce product attribute option data
     * 
     * @param string $data M-Tac attribute option value
     * 
     * @return array
     */
    public function map($data): array
    {
        return [
            'name' => [
                'et' => $data,
            ],
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
<?php
/**
 * Mapper class for mtac product attribute option
 * 
 * @author Web Design Agency OÜ <info@vdisain.ee>
 * @package Vdisain\Mtac\Mappers
 * @since 1.3.0 2023-04-24
 */
namespace Vdisain\Mtac\Mappers;

defined('ABSPATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\MapperContract;
use Vdisain\Plugins\Interfaces\Support\Mapper;

/**
 * Mapper class for mtac product attribute option
 * 
 * @package Vdisain\Mtac\Mappers
 * @since 1.3.0 2023-04-24
 */
class AttributeOptionMapper extends Mapper implements MapperContract, \JsonSerializable
{
    /**
     * Maps mtac product attribute option data to the WooCommerce product attribute option data
     * 
     * @param array $data mtac product attribute data
     * 
     * @return array
     */
    public function map($data): array
    {
        return [
            'name' => [
                'et' => $data['name']
            ],
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
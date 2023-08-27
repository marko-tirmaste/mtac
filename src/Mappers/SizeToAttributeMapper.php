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
class SizeToAttributeMapper extends Mapper implements MapperContract, \JsonSerializable
{
    /**
     * Maps M-Tac product attribute data to WooCommerce product attribute data
     * 
     * @param \Vdisain\Plugins\Interfaces\Support\Collection $data M-Tac product attribute data. $data['key'] for key, $data['data] for data.
     * 
     * @return array
     */
    public function map($data): array
    {
        return [
            'name' => [
                'et' => 'Suurus',
            ],
            'options' => $data->map(function (string $option): AttributeOptionMapper {
                return new AttributeOptionMapper($option);
            }),
            'visible' => true,
            'variation' => true,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
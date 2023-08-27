<?php
/**
 * Mapper class for M-Tac attribute
 * 
 * @author Marko Tirmaste <marko.tirmaste@gmail.com>
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0 2023-07-26
 */
namespace Seeru\Mtac\Mappers;

defined('ABSPATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\MapperContract;
use Vdisain\Plugins\Interfaces\Support\Logger;
use Vdisain\Plugins\Interfaces\Support\Mapper;

/**
 * Mapper class for M-Tac attribute
 * 
 * @package Seeru\Mtac\Mappers
 * @since 1.0.0 2023-07-26
 */
class AttributeMapper extends Mapper implements MapperContract, \JsonSerializable
{
    /**
     * Maps M-Tac product attribute data to WooCommerce product attribute data
     * 
     * @param array $data M-Tac product attribute data.
     *                    $data['key'] for key, $data['data] for data.
     * 
     * @return array
     */
    public function map($data): array
    {
        /* if (vi()->isVerbose()) {
            Logger::describe('AttributeMapper::map() $data');
            Logger::dump($data);
        } */
        
        $names = [
            'brand' => 'Bränd',
            'color' => 'Värv',
            'size' => 'Suurus',
        ];

        // Non-variation attributes
        if (in_array($data['key'], ['brand'])) {
            return [
                'name' => [
                    'et' => $names[$data['key']],
                ],
                'options' => vi_collect([
                    new AttributeOptionMapper(['name' => $data['data'][$data['key']]]),
                ]),
                'visible' => true,
                'variation' => false,
            ];
        }

        // TODO: Maybe separate mapper for every attribute

        // Variation attribute
        if (isset($data['data'][$data['key']])) {
            return [
                'name' => [
                    'et' => $names[$data['key']],
                ],
                'options' => vi_collect([
                    new AttributeOptionMapper(['name' => $data['data']['attribute']]),
                ]),
                'visible' => true,
                'variation' => true,
            ];
        }

        // Variable product attributes
        return [
            'name' => [
                'et' => 'Suurus',
            ],
            'options' => vi_collect($data['data']['sizes']['size'])
                ->map(function (array $size): AttributeOptionMapper {
                    return new AttributeOptionMapper(['name' => $size['attribute']]);
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
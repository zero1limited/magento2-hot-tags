<?php

namespace Zero1\HotTags\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Trigger implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'viewed',
                'label' => __('Viewed')
            ],
            [
                'value' => 'added',
                'label' => __('Added to basket')
            ]
            // ,
            // [
            //     'value' => 'purchased',
            //     'label' => __('Purchased')
            // ]
        ];
    }
}

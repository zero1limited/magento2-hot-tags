<?php

namespace Zero1\HotTags\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Time implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'last_hour',
                'label' => __('Last Hour')
            ],
            [
                'value' => 'today',
                'label' => __('Today')
            ],
            [
                'value' => 'this_week',
                'label' => __('This Week')
            ]
        ];
    }
}

<?php

namespace Ecommage\Sms\Model;

class OtpType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'NO', 'label' => __('Numeric Only')],
            ['value' => 'AN', 'label' => __('Alpha Numeric')]
        ];
    }
}

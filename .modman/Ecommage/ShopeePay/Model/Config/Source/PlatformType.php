<?php

namespace Ecommage\ShopeePay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * @api
 * @since 100.0.2
 */
class PlatformType implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            //['value' => 'app', 'label' => __('Mobile')],
            ['value' => 'mweb', 'label' => __('Mobile Web')],
            ['value' => 'pc', 'label' => __('Desktop')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            //'app'  => __('Mobile'),
            'mweb' => __('Mobile Web'),
            'pc'   => __('Web')
        ];
    }
}

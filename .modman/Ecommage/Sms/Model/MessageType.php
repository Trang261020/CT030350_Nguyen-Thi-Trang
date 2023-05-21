<?php
namespace Ecommage\Sms\Model;

class MessageType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Regular Messages')],
            ['value' => 8, 'label' => __('Unicode Messages')]
        ];
    }
}

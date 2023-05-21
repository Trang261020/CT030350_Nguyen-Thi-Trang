<?php

namespace Ecommage\CustomerFullName\Plugin\Checkout\Block\Checkout;

class LayoutProcessor
{
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array                                            $result
     *
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $result
    ) {
        $layoutRoot     = &$result['components']['checkout']['children']['steps']['children']['shipping-step']
                           ['children']['shippingAddress']['children'];
        $layoutRoot['shipping-address-fieldset']['children']['fullname'] = $this->getComponentFullName('shippingAddress');
        // Loop all payment methods (because billing address is appended to the payments)
        $configuration = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        foreach ($configuration as $paymentGroup => $groupConfig) {
            if (isset($groupConfig['component']) && $groupConfig['component'] === 'Magento_Checkout/js/view/billing-address') {
                $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentGroup]['children']['form-fields']['children']['fullname'] = $this->getComponentFullName('billingAddress');
            }
        }

        return $result;
    }

    /**
     * @param $prefix
     * @return array
     */
    public function getComponentFullName($prefix)
    {
        return [
            'label'      => __('Full Name'),
            'provider'   => 'checkoutProvider',
            'dataScope'  => $prefix.'.fullname',
            'component'  => 'Ecommage_CustomerFullName/js/form/element/fullname',
            'sortOrder'  => 1,
            'visible'    => true,
            'config'     => [
                'customScope' => $prefix,
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
            ],
            'validation' => [
                'required-entry' => true
            ]
        ];
    }
}

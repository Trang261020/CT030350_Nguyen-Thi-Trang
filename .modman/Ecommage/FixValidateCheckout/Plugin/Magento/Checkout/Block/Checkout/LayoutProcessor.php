<?php

namespace Ecommage\FixValidateCheckout\Plugin\Magento\Checkout\Block\Checkout;

use Ecommage\Address\Helper\Data;
use Magento\Checkout\Block\Checkout\AttributeMerger;

class LayoutProcessor
{
    /**
     * @var AttributeMerger
     */
    protected $merger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * LayoutProcessor constructor.
     *
     * @param AttributeMerger $merger
     * @param Data            $helper
     */
    public function __construct(
        AttributeMerger $merger,
        Data $helper
    ) {
        $this->merger = $merger;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array                                            $jsLayout
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $jsLayout
    ) {
        if ($this->helper->isEnabled()) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
            )) {
                $this->_updateAddressFields($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
                );
            }

            // The if billing address should be displayed on Payment method or page
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['afterMethods']['children']
                ['billing-address-form']['children']['form-fields']['children']
            )) {
                $this->_updateAddressFields($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['afterMethods']['children']
                ['billing-address-form']['children']['form-fields']['children']
                );
            } elseif (
                isset($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']['payments-list']['children']
                ) &&
                count($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['payments-list']['children']
                ) > 1
            ) {
                foreach ($jsLayout['components']['checkout']['children']['steps']['children']
                         ['billing-step']['children']['payment']['children']['payments-list']['children'] as $paykey => &$payel) {
                    if (substr($paykey, -5) === '-form') {
                        if(isset($payel['children']['form-fields']['children'])){
                            $this->_updateAddressFields($payel['children']['form-fields']['children']);
                        }
                    }
                }
            }
        } elseif (!$this->helper->isEnabled()) {
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
            )) {
                $this->_hideAddressFields($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
                );
            }

            // The if billing address should be displayed on Payment method or page
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['afterMethods']['children']
                ['billing-address-form']['children']['form-fields']['children']
            )) {
                $this->_hideAddressFields($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['afterMethods']['children']
                ['billing-address-form']['children']['form-fields']['children']
                );
            } elseif (
                isset($jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']['payments-list']['children']
                ) &&
                count($jsLayout['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']['payments-list']['children']
                ) > 1
            ) {
                foreach ($jsLayout['components']['checkout']['children']['steps']['children']
                         ['billing-step']['children']['payment']['children']['payments-list']['children'] as $paykey => &$payel) {
                    if (substr($paykey, -5) === '-form' && isset($payel['children']['form-fields']['children'])) {
                        $this->_hideAddressFields($payel['children']['form-fields']['children']);
                    }
                }
            }
        }
        if ($this->helper->checkAmastyOneStepCheckoutEnable()) {
            if (isset($jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['block-amasty-checkout-statistic'])) {
                $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']
                ['block-amasty-checkout-statistic']['children']['block-amasty-checkout-statistic']['children']
                ['amasty-checkout-statistic']['component']
                    = 'Ecommage_Address/js/checkout/model/statistic';
            }
        }

        return $jsLayout;
    }

    private function _updateAddressFields(&$fields)
    {
        $fields['country_id']['sortOrder']                    = 99;
        $fields['region']['sortOrder']                        = 100;
        $fields['region_id']['sortOrder']                     = 101;
        $fields['city']['visible']                            = false;
        $fields['city']['sortOrder']                          = 102;
//        $fields['city']['dataScope']                          = 'shippingAddress.city';
        $fields['city_id']['sortOrder']                       = 104;
        $fields['city_id']['component']                       = 'Ecommage_Address/js/components/form/element/city';
        $fields['city_id']['config']['elementTmpl']           = 'ui/form/element/select';
        $fields['city_id']['config']['template']              = 'ui/form/field';
        $fields['city_id']['provider']                        = 'checkoutProvider';
        $fields['city_id']['filterBy']['target']              = '${ $.provider }:${ $.parentScope }.region_id';
        $fields['city_id']['filterBy']['field']               = 'region_id';
        $fields['city_id']['validation']['required-entry']    = true;
        $fields['ward']['visible']                            = false;
        $fields['ward']['sortOrder']                          = 106;
        $fields['ward']['provider']                           = 'checkoutProvider';
        $fields['ward_id']['sortOrder']                       = 108;
        $fields['ward_id']['component']                       = 'Ecommage_Address/js/components/form/element/ward';
        $fields['ward_id']['config']['elementTmpl']           = 'ui/form/element/select';
        $fields['ward_id']['config']['template']              = 'ui/form/field';
        $fields['ward_id']['provider']                        = 'checkoutProvider';
        $fields['ward_id']['filterBy']['target']              = '${ $.provider }:${ $.parentScope }.city_id';
        $fields['ward_id']['filterBy']['field']               = 'city_id';
        $fields['ward_id']['validation']['required-entry']    = true;
        $fields['telephone']['validation']['validate-digits'] = true;
        $fields['postcode']['validation']['validate-digits']  = true;
        $fields['postcode']['component']                      = 'Ecommage_Address/js/components/post-code';
        return $fields;
    }

    private function _hideAddressFields(&$fields)
    {
        $fields['country_id']['sortOrder'] = 99;
        $fields['city']['visible']         = true;
        $fields['city']['sortOrder']       = 102;
        $fields['city_id']['visible']      = false;
        $fields['ward']['visible']         = false;
        $fields['ward']['sortOrder']       = 106;
        $fields['ward_id']['visible']      = false;
        $fields['postcode']['component']   = 'Ecommage_Address/js/components/post-code';
        return $fields;
    }
}

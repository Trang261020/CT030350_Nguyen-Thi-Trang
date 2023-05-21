<?php

namespace Ecommage\FixValidateCheckout\Plugin\ValidationRules;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Quote\Model\Quote;

class ShippingAddressValidation
{
    /**
     * @var string
     */
    protected $generalMessage;

    /**
     * @var ValidationResultFactory
     */
    protected $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param string $generalMessage
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        string $generalMessage=''
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->generalMessage = $generalMessage;
    }

    /**
     * @inheritdoc
     */
    public function aroundValidate(
        \Magento\Quote\Model\ValidationRules\ShippingAddressValidationRule $subject,
        callable $proceed,
        \Magento\Quote\Model\Quote $quote): array
    {
        $validationErrors = [];

        if (!$quote->isVirtual()) {
            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            if(!$shippingAddress->getCity()){
                $shippingAddress->setcity($billingAddress->getCity());
                $shippingAddress->setCityId($billingAddress->getCityId());
            }
            if(!$shippingAddress->getTelephone()){
                $shippingAddress->setTelephone($billingAddress->getTelephone());
            }
            $streetShipping = $shippingAddress->getStreet();
            if(!$streetShipping['0']){
                $street = $billingAddress->getStreet();
                $shippingAddress->setStreet($street['0']);
            }
            $shippingAddress->setStoreId($quote->getStoreId());
            $validationResult = $shippingAddress->validate();

            if ($validationResult !== true) {
                $validationErrors = [__($this->generalMessage)];
            }

            if (is_array($validationResult)) {
                $validationErrors = array_merge($validationErrors, $validationResult);
            }
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }
}

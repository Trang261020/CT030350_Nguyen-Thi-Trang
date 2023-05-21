<?php

namespace Ecommage\FixValidateCheckout\Plugin\ValidationRules;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Quote\Model\Quote;

class BillingAddressValidation
{
    /**
     * @var \Ecommage\Address\Model\CityFactory
     */
    protected $_cityFactory;
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
        \Ecommage\Address\Model\CityFactory $cityFactory,
        ValidationResultFactory $validationResultFactory,
        string $generalMessage=''
    ) {
        $this->_cityFactory = $cityFactory;
        $this->validationResultFactory = $validationResultFactory;
        $this->generalMessage = $generalMessage;
    }

    /**
     * @inheritdoc
     */
    public function aroundValidate(
        \Magento\Quote\Model\ValidationRules\BillingAddressValidationRule $subject,
        callable $proceed,
        Quote $quote): array
    {
        $validationErrors = [];
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setStoreId($quote->getStoreId());

        if(!$billingAddress->getCity()){
            $cityId = $billingAddress->getCityId();
            $billingAddress->setCity($this->getCityModel($cityId)->getName());
        }

        if(!$billingAddress->getStreet()){
            $billingAddress->setStreet($this->getStreet());
        }

        $validationResult = $billingAddress->validate();
        if ($validationResult !== true) {
            $validationErrors = [__($this->generalMessage)];
        }

        if (is_array($validationResult)) {
            $validationErrors = array_merge($validationErrors, $validationResult);
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }

    /**
     * @param $cityId
     * @return \Ecommage\Address\Model\City
     */
    public function getCityModel($cityId){
        /** @var \Ecommage\Address\Model\City $cityModel */
        return $this->_cityFactory->create()->load($cityId);
    }

}

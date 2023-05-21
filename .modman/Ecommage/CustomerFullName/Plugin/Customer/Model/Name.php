<?php

namespace Ecommage\CustomerFullName\Plugin\Customer\Model;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;

class Name
{
    /**
     * @var CustomerMetadataInterface
     */
    protected $_customerMetadataService;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param CustomerMetadataInterface $customerMetadataService
     * @param Escaper|null $escaper
     */
    public function __construct(
        CustomerMetadataInterface $customerMetadataService,
        Escaper $escaper = null
    ) {
        $this->_customerMetadataService = $customerMetadataService;
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(Escaper::class);
    }

    /**
     * @inheritdoc
     */
    public function afterGetCustomerName(\Magento\Customer\Helper\View $subject, $result, CustomerInterface $customerData)
    {

        if($customerData->getFirstname() == $customerData->getLastname()){
            return $customerData->getFirstname();
        }
        return $result;
    }
}

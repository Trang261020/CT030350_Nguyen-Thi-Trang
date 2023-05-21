<?php
namespace Ecommage\CustomCustomerLogin\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Sparsh\MobileNumberLogin\Helper\Data;

class AuthenticateMobileNumber
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     */
    public function __construct
    (
        Registry $registry,
        StoreManagerInterface $storeManager,
        Data $data
    )
    {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->helperData = $data;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(\Sparsh\MobileNumberLogin\Observer\AuthenticateMobileNumber $subject, $observer)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $isModuleEnabled  = $this->helperData->isActive($storeId);

        if ($isModuleEnabled) {
            $this->registry->unregister('country_code');
            $this->registry->register('country_code', 'vn');
        }
    }
}

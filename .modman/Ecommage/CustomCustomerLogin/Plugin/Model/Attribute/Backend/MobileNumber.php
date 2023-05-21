<?php
namespace Ecommage\CustomCustomerLogin\Plugin\Model\Attribute\Backend;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Sparsh\MobileNumberLogin\Setup\InstallData;

class MobileNumber
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $message;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * MobileNumber constructor.
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Message\ManagerInterface $managerInterFace,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->urlInterface  = $urlInterface;
        $this->message = $managerInterFace;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Validates if the customer mobile number is unique.
     *
     * @param \Magento\Framework\DataObject $object
     * @return \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
     * @throws LocalizedException
     */
    public function aroundBeforeSave(\Sparsh\MobileNumberLogin\Model\Attribute\Backend\MobileNumber $subject, callable $proceed , $object)
    {
        $object->setData(InstallData::COUNTRY_CODE,'vn');
        $mobileNumber = $object->getData(InstallData::MOBILE_NUMBER);
        $countryCode = $object->getData(InstallData::COUNTRY_CODE);
        if ($mobileNumber && $countryCode) {
            $collection = $this->customerCollectionFactory->create()
                                                          ->addAttributeToFilter(InstallData::MOBILE_NUMBER, $mobileNumber)
                                                          ->addAttributeToFilter(InstallData::COUNTRY_CODE, $countryCode);
            if ($object->getSharingConfig()->isWebsiteScope()) {
                $collection->addAttribuTeToFilter('website_id', (int) $object->getData('website_id'));
            }
            if ($object->getData('entity_id')) {
                $collection->addAttribuTeToFilter('entity_id', ['neq' => (int) $object->getData('entity_id')]);
            }
            if ($collection->getSize() > 0) {
                $this->message->addNotice(sprintf(__('<a class="mess-same-mobile" href="%s">%s</a>'),'#amsl-login-content',__('Click sign in now')));
                throw new LocalizedException(__('A customer with the same mobile number already exists in an associated website.'));
            }
        }

        return $proceed($object);
    }
}

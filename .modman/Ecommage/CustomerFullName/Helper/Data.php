<?php

namespace Ecommage\CustomerFullName\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var Amasty\SocialLogin\Model\ResourceModel\Social\CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Amasty\SocialLogin\Model\ResourceModel\Social\CollectionFactory $collectionFactory,
        Context $context
    )
    {
        $this->session = $session;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function isCheckTypeLogin()
    {
        $type = [];
        $customer = $this->session->getId();
        $collection = $this->collectionFactory->create();
        if ($customer)
        {
            $type = $collection->addFieldToFilter('customer_id',$customer)->getData();
        }
        return $type;
    }
}
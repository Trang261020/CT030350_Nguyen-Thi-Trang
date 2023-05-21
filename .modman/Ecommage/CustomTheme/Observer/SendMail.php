<?php

namespace Ecommage\CustomTheme\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendMail implements ObserverInterface
{
    /**
     * @var \Ecommage\Sms\Helper\Data
     */
    protected $helper;

    /**
     * @param \Ecommage\Sms\Helper\Data $data
     */
    public function __construct
    (
        \Ecommage\Sms\Helper\Data $data
    )
    {
        $this->helper = $data;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        $message = $this->getMessageCreateOtp();
        if ($customer->getId())
        {
            $this->helper->curlApiCall($message,$customer->getCustomAttribute('mobile_number')->getValue(),$customer->getId());
        }
    }

    /**
     * @return array|string|string[]
     */
    public function getMessageCreateOtp()
    {
        $storeName = $this->helper->getApiSourceAdd();
        $storeUrl = $this->helper->getStoreUrl();
        $codes = array('{{shop_name}}','{{shop_url}}');
        $accurate = array($storeName,$storeUrl);
        return str_replace($codes,$accurate,$this->helper->getCreateOtpTemplate());
    }
}
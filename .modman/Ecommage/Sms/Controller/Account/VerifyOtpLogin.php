<?php

namespace Ecommage\Sms\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\Sms\Helper\Data;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class VerifyOtpLogin extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var CustomerFactory
     */
    protected $customer;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var PageFactory
     */
    protected $pageFactory;
    /**
     * @var
     */
    protected $date;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Context                                     $context,
        Data                                        $helper,
        Session                                     $customerSession,
        CustomerFactory                             $customer,
        ManagerInterface                            $messageManager,
        StoreManagerInterface                       $storeManager,
        PageFactory                                 $pageFactory

    )
    {
        $this->date = $date;
        $this->helper = $helper;
        $this->session = $customerSession;
        $this->customer = $customer;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $preUrl = $this->_request->getServer('HTTP_REFERER');
        $pos = strpos($preUrl, 'customer/account/login');
        if ($pos !== false) {
            return $this->pageFactory->create();
        } else {
            $otpCode = $this->getRequest()->getParam('otpCode');
            $mobileNumber = $this->getRequest()->getParam('mobileNumber');
            if (substr($mobileNumber, 0, 1) != 0) {
                $mobileNumber = '0' . $mobileNumber;
            }
            $isExistOtp = $this->helper->verifyLoginOTPCode($mobileNumber, $otpCode);
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            if (!empty($isExistOtp->getData())) {
                // check time 3minus
                $timeOut = 0;
                $datetime1 = date_create($this->date->gmtDate());
                $datetime2 = date_create($isExistOtp->getCreatedTime());
                $interval = date_diff($datetime2, $datetime1);

                if ($interval->i >= 3) {
                    $timeOut = 1;
                }

                if($timeOut){
                    $this->messageManager->addErrorMessage(
                        __("Mã OTP hết hiệu lực")
                    );
                    $redirect->setUrl($this->helper->getBaseUrl());
                    return $redirect;
                }

                $storeId = $this->helper->getStore()->getId();
                $customer = $this->customer->create()->getCollection()->addFieldToFilter("mobile_number", $mobileNumber)->addFieldToFilter('store_id', $storeId);
                if (count($customer) == 1) {
                    $customer = $customer->getFirstItem();
                    $this->session->setCustomerAsLoggedIn($customer);
                    $this->session->regenerateId();
                    $redirect->setUrl($this->helper->getBaseUrl() . 'customer/account/index');
                }
            } else {
                $this->messageManager->addErrorMessage(
                    __("OTP Code Invalid")
                );
                $redirect->setUrl($this->helper->getBaseUrl());
            }
            return $redirect;
        }

    }

}

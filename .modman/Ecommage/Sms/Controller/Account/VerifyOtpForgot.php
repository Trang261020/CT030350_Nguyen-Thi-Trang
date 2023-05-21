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

class VerifyOtpForgot extends \Magento\Framework\App\Action\Action
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

    public function __construct(
        Context $context,
        Data $helper,
        Session $customerSession,
        CustomerFactory $customer,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        PageFactory $pageFactory

    ){
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
        $mobileNumber = $this->getRequest()->getParam('login') ? $this->getRequest()->getParam('login')['username'] : '';
        if(substr($mobileNumber,0,1) != 0){
            $mobileNumber = '0'.$mobileNumber;
        }
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if($mobileNumber){
            $isExist = $this->helper->verfiyForgotOtp($mobileNumber);
            $storeId = $this->helper->getStore()->getId();
            $customer = $this->customer->create()->getCollection()->addFieldToFilter("mobile_number", $mobileNumber)->addFieldToFilter('store_id',$storeId);
            if(count($customer) == 1){
                if($isExist) {
                    $this->messageManager->addSuccessMessage(
                        __("Mobile number %1 will receive an sms with a link to reset your password.", $mobileNumber)
                    );
                }
            }else{
                $this->messageManager->addErrorMessage(
                    __("Mobile number does not exist.")
                );
            }
        }else{
            $this->messageManager->addErrorMessage(
                __("Mobile number does not exist.")
            );
        }

        return $redirect->setUrl($this->helper->getBaseUrl()."customer/account/forgotpassword");;

    }

}

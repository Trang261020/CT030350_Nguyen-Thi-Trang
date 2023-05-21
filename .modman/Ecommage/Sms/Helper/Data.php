<?php
namespace Ecommage\Sms\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Ecommage\Sms\Model\LoginotpmodelFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Math\Random;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Ecommage\Sms\Model\Loginotpmodel;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SMS_MODULEOPTION_ENABLE = 'sms/moduleoption/enable';
    const SMS_GENERAL_OTPLENGTH = 'sms/general/otplength';
    const SMS_GENERAL_OTPTYPE = 'sms/general/otptype';
    const SMS_FORGOTOTPSEND_MESSAGE = 'sms/forgototpsend/message';
    const SMS_CREATE_ACCOUNT_MESSAGE = 'sms/createaccount/message';
    const SMS_LOGINOTPSEND_MESSAGE = 'sms/loginotpsend/message';
    const SMS_API_MESSAGETYPE = 'sms/api/messagetype';
    const SMS_API_APIURL = 'sms/api/apiurl';
    const SMS_API_USERNAME = 'sms/api/username';
    const SMS_API_PASSWORD = 'sms/api/password';
    const SMS_API_SOURCEADD = 'sms/api/sourceadd';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerFactory
     */
    protected $customer;
    /**
     * @var LoginotpmodelFactory
     */
    protected $loginotpmodelFactory;
    /**
     * @var ForgototpmodelFactory
     */
    protected $forgototpmodelFactory;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Curl
     */
    protected $curl;
    /**
     * @var UrlInterface
     */
    protected $url;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var Random
     */
    protected $random;
    /**
     * @var AccountManagement
     */
    protected $accountManagement;
    /**
     * @var Loginotpmodel
     */
    protected $loginotpmodel;

    protected $_userHelper;

    public function __construct
    (
        \Magento\User\Helper\Data $userHelper,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CustomerFactory $customer,
        LoginotpmodelFactory $loginotpmodelFactory,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        LoggerInterface $logger,
        Curl $curl,
        UrlInterface $url,
        ResultFactory $resultFactory,
        Random $random,
        AccountManagement $accountManagement,
        CustomerRepository $customerRepository,
        Loginotpmodel $loginotpmodel
    )
    {
        $this->_userHelper = $userHelper;
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customer = $customer;
        $this->loginotpmodelFactory = $loginotpmodelFactory;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->url = $url;
        $this->resultFactory = $resultFactory;
        $this->random = $random;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
        $this->loginotpmodel = $loginotpmodel;
    }

    /**
     * @return mixed
     */
    public function isEnable()
    {
        return $this->scopeConfig->getValue(
            self::SMS_MODULEOPTION_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getToken(){
        return $this->_userHelper->generateResetPasswordLinkToken();
    }

    /**
     * @return string
     */
    public function generateRandomString()
    {
        $length = $this->getOtpStringlenght();
        if($this->getOtpStringtype() == "NO"){
            $randomString = substr(str_shuffle("0123456789"), 0, $length);
        }
        else{
            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        }

        return $randomString;
    }

    /**
     * @param $fromStore
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreUrl($fromStore = true)
    {
        return $this->getStore()->getUrl();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreName()
    {
        return $this->getStore()->getName();
    }

    /**
     * @return mixed
     */
    public function getOtpStringlenght()
    {
        return $this->scopeConfig->getValue(
            self::SMS_GENERAL_OTPLENGTH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getOtpStringtype()
    {
        return $this->scopeConfig->getValue(
            self::SMS_GENERAL_OTPTYPE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getForgotOtpTemplate()
    {
        return $this->scopeConfig->getValue(
            self::SMS_FORGOTOTPSEND_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCreateOtpTemplate()
    {
        return $this->scopeConfig->getValue(
            self::SMS_CREATE_ACCOUNT_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getLoginOtpTemplate()
    {
        return $this->scopeConfig->getValue(
            self::SMS_LOGINOTPSEND_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApiTypeMessage()
    {
        return $this->scopeConfig->getValue(
            self::SMS_API_MESSAGETYPE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::SMS_API_APIURL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApiUsername()
    {
        return $this->scopeConfig->getValue(
            self::SMS_API_USERNAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getApiPassword()
    {
        $password = $this->scopeConfig->getValue(
            self::SMS_API_PASSWORD,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($password);
    }

    /**
     * @return mixed
     */
    public function getApiSourceAdd()
    {
        return $this->scopeConfig->getValue(
            self::SMS_API_SOURCEADD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $randomCode
     * @return array|mixed|string|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLoginOtpMessage($randomCode)
    {
        $storeName = $this->getApiSourceAdd();
        $storeUrl = $this->getStoreUrl();
        $codes = array('{{shop_name}}','{{shop_url}}','{{random_code}}');
        $accurate = array($storeName,$storeUrl,$randomCode);
        return str_replace($codes,$accurate,$this->getLoginOtpTemplate());

    }

    public function getForgotSmsMessage($link){
        $storeName = $this->getApiSourceAdd();
        $storeUrl = $this->getStoreUrl();
        $codes = array('{{shop_name}}','{{shop_url}}','{{link_reset_password}}');
        $accurate = array($storeName,$storeUrl,$link);
        return str_replace($codes,$accurate,$this->getForgotOtpTemplate());
    }

    /**
     * @param $randomCode
     * @return array|mixed|string|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getForgotOtpMessage($link)
    {
        $storeName = $this->getStoreName();
        $storeUrl = $this->getStoreUrl();


        $codes = array('{{shop_name}}','{{shop_url}}','{{link_reset_password}}');
        $accurate = array($storeName,$storeUrl,$link);
        return str_replace($codes,$accurate,$this->getForgotOtpTemplate());
    }

    /**
     * @param $randomCode
     * @return array|mixed|string|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApi($info)
    {
        $ApiUrl = $this->getApiUrl();
        $codes = array('{{username}}','{{password}}','{{source_addr}}','{{dest_addr}}','{{message}}','{{type}}','{{request_id}}');
        return str_replace($codes,$info,$ApiUrl);
    }


    /**
     * @param $mobile
     * @return string
     */
    public function sendLoginOTPCode($mobile)
    {
        try{
            $storeId = $this->getStore()->getId();

            $customer = $this->customer->create()->getCollection()->addFieldToFilter("mobile_number", $mobile)->addFieldToFilter('store_id',$storeId);
            if(count($customer) != 1){
                return false;
            }
            $otpModels = $this->loginotpmodelFactory->create();
            $collection = $otpModels->getCollection();
            $collection->addFieldToFilter('mobile_number', $mobile);
            $date = $this->dateTime->gmtDate();
            $randomCode = $this->generateRandomString();
            $message = $this->getLoginOtpMessage($randomCode);

            if(count($collection)){
                $this->loginotpmodel = $this->loginotpmodelFactory->create()->load($mobile,'mobile_number');
            }
            $this->loginotpmodel->setRandomCode($randomCode);
            $this->loginotpmodel->setCreatedTime($date);
            $this->loginotpmodel->setIsVerify(0);
            $this->loginotpmodel->setMobileNumber($mobile);
            $this->loginotpmodel->save();

            return $this->curlApiCall($message,$mobile,$customer->getFirstItem()->getId());
        }catch(\Exception $e)
        {
            return $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param $mobile
     * @param $randome
     * @return int|null
     */
    public function verifyLoginOTPCode($mobile,$randome){

        $otpModels = $this->loginotpmodelFactory->create();
        $collection = $otpModels->getCollection();
        $collection->addFieldToFilter('mobile_number', $mobile);
        $collection->addFieldToFilter('random_code', $randome);
        return $collection->getFirstItem();
    }

    /**
     * @param $mobile
     * @param $otp
     * @return bool
     * @throws NoSuchEntityException
     */
    public function verfiyForgotOtp($mobile){
        $storeId = $this->getStore()->getId();
        $customer = $this->customer->create()->getCollection()->addFieldToFilter("mobile_number", $mobile)->addFieldToFilter('store_id',$storeId)->getFirstItem();
        if(!$customer->getId()){
            return false;
        }
        $customer = $this->customerRepository->getById($customer->getId());
        $newPasswordToken = $this->random->getUniqueHash();
        $this->accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);
        $link = $this->getLinkResetPass().'?token='.$newPasswordToken;
        $message = $this->getForgotOtpMessage($link);
        return $this->curlApiCall($message,$mobile,$customer->getId());
    }

    public function curlApiCall($message,$mobileNumber,$customerId)
    {
        if($this->isEnable())
        {
            try {
                $getData = array(
                    $this->getApiUsername(),
                    $this->getApiPassword(),
                    $this->getApiSourceAdd(),
                    $mobileNumber,
                    $message,
                    $this->getApiTypeMessage(),
                    $customerId
                );

                $fullApiUrl = $this->getApi($getData);
                $this->curl->get($fullApiUrl);
                $messageId = $this->curl->getBody();
                if((int)$messageId <= 1000){
                    $this->logger->debug($this->curl->getBody());
                    return false;
                }
                return true;
            }catch (\Exception $e){
                $this->logger->debug($e->getMessage());
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore(){
        return $this->storeManager->getStore();
    }

    /**
     * @return string
     */
    public function getUrlLoginOtp(){
        return $this->url->getUrl('sms/account/sendotplogin');
    }

    /**
     * @return string
     */
    public function getUrlVerifyOtp(){
        return $this->url->getUrl('sms/account/verifyotplogin');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl(){
        return $this->getStore()->getBaseUrl();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlResetPassword(){
        return $this->url->getUrl('sms/account/verifyotpforgot');
    }

    /**
     * @return string
     */
    public function getUrlForgotPass(){
        return $this->url->getUrl('customer/account/forgotpasswordpost');
    }

    /**
     * @return string
     */
    public function getLinkResetPass(){
        return $this->url->getUrl('customer/account/createPassword');
    }

    /**
     * @return string
     */
    public function getUrlLoginPost(){
        return $this->url->getUrl('customer/account/loginPost');
    }
}

<?php
namespace Ecommage\Sms\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;

class VerifyOtpLogin extends Template
{
    public function __construct
    (
        Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return array|false|mixed|null
     */
    public function getMobileNumber(){
        if($this->hasData('mobileNumber')){
            return $this->getData('mobileNumber');
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getReqMobileNumber(){
        return $this->_request->getParam('mobileNumber');
    }

}

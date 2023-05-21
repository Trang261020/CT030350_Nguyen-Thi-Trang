<?php

namespace Ecommage\CustomCustomerLogin\Plugin\Customer\Controller\Address;

class FormPost
{
    public function beforeExecute(\Magento\Customer\Controller\Address\FormPost $subject)
    {
        $param = $subject->getRequest()->getParams();
        if (!empty($param))
        {
            $subject->getRequest()->setParam('lastname','');
            $subject->getRequest()->setParam('firstname',$param['fullname']);
        }
    }
}
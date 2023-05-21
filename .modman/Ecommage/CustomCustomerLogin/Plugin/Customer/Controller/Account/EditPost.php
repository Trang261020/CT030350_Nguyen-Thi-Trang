<?php

namespace Ecommage\CustomCustomerLogin\Plugin\Customer\Controller\Account;

class EditPost
{
    public function beforeExecute(\Magento\Customer\Controller\Account\EditPost $subject)
    {
        $param = $subject->getRequest()->getParams();
        if (!empty($param))
        {
            $subject->getRequest()->setParam('lastname','');
            $subject->getRequest()->setParam('firstname',$param['fullname']);
        }
    }
}
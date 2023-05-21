<?php
namespace Ecommage\CustomCustomerLogin\Plugin\Model;

use Magento\Framework\Registry;

class AccountManagement
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Authenticate a customer by username and password
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeAuthenticate(\Magento\Customer\Model\AccountManagement $subject)
    {
        $this->registry->unregister('country_code');
        $this->registry->register('country_code','vn');
    }
}

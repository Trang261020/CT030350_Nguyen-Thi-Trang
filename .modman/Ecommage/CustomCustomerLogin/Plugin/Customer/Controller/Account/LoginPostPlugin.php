<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Social Login Base for Magento 2
 */

namespace Ecommage\CustomCustomerLogin\Plugin\Customer\Controller\Account;

use Magento\Customer\Controller\Account\LoginPost;
use Magento\Framework\Registry;

class LoginPostPlugin
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @param CreatePost $subject
     * @param $result
     * @return mixed
     */
    public function beforeExecute(LoginPost $subject)
    {
        $this->registry->unregister('country_code');
        $this->registry->register('country_code', 'vn');
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecommage\CustomTheme\Block\Account;


/**
 * Class Link
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Link extends \Magento\Customer\Block\Account\Link
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context              $httpContext,
        \Magento\Customer\Model\Url                      $customerUrl,
        array                                            $data = []
    )
    {
        $this->httpContext = $httpContext;
        parent::__construct($context, $customerUrl, $data);
    }

    /**
     * Checking customer login status
     *
     * @return bool
     */
    public function customerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->customerLoggedIn()) {
            return parent::_toHtml();
        }

        return '';
    }
}

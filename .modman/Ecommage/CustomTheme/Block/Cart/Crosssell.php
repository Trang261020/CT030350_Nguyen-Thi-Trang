<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecommage\CustomTheme\Block\Cart;

use Magento\Store\Model\ScopeInterface;

class Crosssell extends \Magento\Checkout\Block\Cart\Crosssell
{
    const XML_PATH_LIMIT = 'checkout/cart/max_items_display_count';
    /**
     * Items quantity will be capped to this value
     *
     * @var int
     */
    protected $_maxItemCount = 10;

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->initMaxItem();
    }

    /**
     * @return $this
     */
    public function initMaxItem()
    {
        $this->_maxItemCount = (int)$this->_scopeConfig->getValue(
            self::XML_PATH_LIMIT,
            ScopeInterface::SCOPE_STORE
        );
        return $this;
    }
}

<?php

namespace Ecommage\ProductIndexFreeGift\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductUpdate implements ObserverInterface
{
    /**
     * @var \Ecommage\ProductIndexFreeGift\Helper\FreeGift
     */
    protected $helper;

    /**
     * RuleUpdate constructor.
     *
     * @param \Ecommage\ProductIndexFreeGift\Helper\FreeGift $helper
     */
    public function __construct(
        \Ecommage\ProductIndexFreeGift\Helper\FreeGift $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getData('product');
        $this->helper->reIndexProduct($product);
    }
}

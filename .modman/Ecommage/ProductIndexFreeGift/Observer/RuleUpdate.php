<?php

namespace Ecommage\ProductIndexFreeGift\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RuleUpdate implements ObserverInterface
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
        $rule = $observer->getEvent()->getRule();
        if ($amPromo = $rule->getData('ampromo_rule')) {
            if (!empty($amPromo->getData('sku')) && $amPromo->getData('type') == 1) {
                $this->helper->reIndexRule($rule);
            }
        }
    }
}

<?php

namespace Ecommage\CustomTheme\Observer;

use Magento\Framework\Registry;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Block\Product\ListProduct;

class ProductRandom implements ObserverInterface
{
    const RANDOM_PRODUCT = 'random_product';
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * ProductList constructor.
     *
     * @param Registry $_coreRegistry
     */
    public function __construct(
        Registry $_coreRegistry
    ) {
        $this->_coreRegistry = $_coreRegistry;
    }

    /**
     * Execute observer.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof ListProduct) {
            $toolbar = $block->getToolbarBlock();
            $data    = $toolbar->getRequest()->getParams();
            if ($this->isRandom($data)) {
                $this->_coreRegistry->register(self::RANDOM_PRODUCT, $toolbar);
            }
        }
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function isRandom($data)
    {
        unset($data['id']);
        if (empty($data)) {
            return true;
        }

        return false;
    }
}

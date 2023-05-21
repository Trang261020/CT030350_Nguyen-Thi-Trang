<?php

namespace Ecommage\ShowBrandCategory\Observer;

class BeforeSave implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $request = $observer->getEvent()->getRequest();
        $brandIds = $category->getCategoryBrands();
        if (!$request->getParam('category_brands')) {
            $brandIds = [];
        }

        if (is_array($brandIds)) {
            $category->setCategoryBrands(implode(',', $brandIds));
        }
        return $this;
    }
}

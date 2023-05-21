<?php

namespace Ecommage\FixSortCore\Observer\Block\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ListProduct implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct
    (
        RequestInterface $request
    )
    {
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        $request = $this->request->getParam('product_list_order',null);
        $collection =  $observer->getEvent()->getData('collection');
        if ($request == 'name_desc')
        {
            $collection->setOrder('name','desc');
        }
        return $collection;
    }
}
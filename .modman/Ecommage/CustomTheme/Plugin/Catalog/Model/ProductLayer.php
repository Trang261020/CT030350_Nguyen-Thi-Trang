<?php

namespace Ecommage\CustomTheme\Plugin\Catalog\Model;

use Magento\Framework\Registry;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Ecommage\CustomTheme\Observer\ProductRandom;

class ProductLayer
{
    /**
     * @var null
     */
    protected $_productCollection = null;
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var Layer
     */
    protected $_catalogLayer;
    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * ProductList constructor.
     *
     * @param Resolver          $layerResolver
     * @param Registry          $_coreRegistry
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Resolver $layerResolver,
        Registry $_coreRegistry,
        CollectionFactory $productCollectionFactory
    ) {
        $this->_coreRegistry             = $_coreRegistry;
        $this->_catalogLayer             = $layerResolver->get();
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param $pageNum
     * @param $pageSize
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _getProductCollectionRandom($pageNum, $pageSize)
    {
        if ($this->_productCollection === null) {
            /** @var Collection $collection */
            $collection = $this->_productCollectionFactory->create();
            $this->_catalogLayer->prepareProductCollection($collection);
            $collection->getSelect()->orderRand();
            $collection->addStoreFilter();
            //$collection->setPage($pageNum, $pageSize);
            $this->_productCollection = $collection;
        }

        return $this->_productCollection;
    }

    /**
     * @param Layer      $layer
     * @param Collection $collection
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductCollection(Layer $layer, $collection)
    {
        $toolbar = $this->_coreRegistry->registry(ProductRandom::RANDOM_PRODUCT);
        if ($toolbar) {
            $pageSize          = (int)$toolbar->getLimit();
            $pageNum           = (int)$toolbar->getCurrentPage();
            $productCollection = $this->_getProductCollectionRandom($pageNum, $pageSize);
            $productCollection->getSelect()->orderRand();
            $products   = $productCollection->getData();
            $productIds = array_column($products, 'entity_id');
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
            $this->_coreRegistry->unregister(ProductRandom::RANDOM_PRODUCT);
        }

        return $collection;
    }
}

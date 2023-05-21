<?php

namespace Ecommage\ProductIndexFreeGift\Helper;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Ecommage\ProductIndexFreeGift\Model\ProductGiftFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Ecommage\ProductIndexFreeGift\Model\ProductGift;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\QuoteFactory;
use Amasty\Promo\Model\RuleResolver;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\DataObject;

class FreeGift extends AbstractHelper
{
    const XML_CONFIG_PATH = 'cataloginventory/gift_options/';
    /**
     * @var State
     */
    protected $appState;
    /**
     * @var Timezone
     */
    protected $timezone;
    /**
     * @var RuleResolver
     */
    protected $ruleResolver;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var ProductGiftFactory
     */
    protected $productGiftFactory;
    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * FreeGift constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param RuleCollectionFactory    $ruleCollectionFactory
     * @param ResourceConnection       $resourceConnection
     * @param ProductGiftFactory       $productGiftFactory
     * @param StockRegistryInterface   $stockRegistry
     * @param QuoteFactory             $quoteFactory
     * @param RuleResolver             $ruleResolver
     * @param Timezone                 $timezone
     * @param Context                  $context
     * @param State                    $appState
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        ResourceConnection $resourceConnection,
        ProductGiftFactory $productGiftFactory,
        StockRegistryInterface $stockRegistry,
        QuoteFactory $quoteFactory,
        RuleResolver $ruleResolver,
        Timezone $timezone,
        Context $context,
        State $appState
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->ruleCollectionFactory    = $ruleCollectionFactory;
        $this->productGiftFactory       = $productGiftFactory;
        $this->resourceConnection       = $resourceConnection;
        $this->stockRegistry            = $stockRegistry;
        $this->quoteFactory             = $quoteFactory;
        $this->ruleResolver             = $ruleResolver;
        $this->timezone                 = $timezone;
        $this->appState                 = $appState;
        parent::__construct($context);
    }

    /**
     * @throws Exception
     */
    public function reIndexRule($rule)
    {
        $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'processRule'],
            ['rule' => $rule]
        );
    }

    /**
     * @throws Exception
     */
    public function reIndexAll()
    {
        $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'processFreeGift']
        );
    }

    /**
     * @param array $ids
     *
     * @throws Exception
     */
    public function reIndexProductIds(array $ids)
    {
        $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'updateFreeGift'],
            ['product' => $ids]
        );
    }

    /**
     * @param $product
     *
     * @throws Exception
     */
    public function reIndexProduct($product)
    {
        $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'updateFreeGift'],
            ['product' => $product]
        );
    }

    /**
     * @param $rule
     *
     * @return $this
     */
    public function processRule($rule)
    {
        $data     = [];
        $products = $this->getProductCollection();
        $this->deleteByRuleId($rule->getId());
        foreach ($products as $product) {
            if ($freeGits = $this->getFreeGiftData($product, $rule)) {
                $data[] = $freeGits;
            }
        }

        $this->saveProductGiftMulti($data);
        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function updateFreeGift($data)
    {
        $freeGits = [];
        $product  = $data['product'] ?? null;
        $rules    = $this->getRuleCollection();
        if ($product instanceof Product) {
            foreach ($rules as $rule) {
                if ($gifts = $this->getFreeGiftData($product, $rule)) {
                    $freeGits[] = $gifts;
                }
            }
            $this->saveProductGiftMulti($freeGits, true);
            return $this;
        }

        if (is_array($product)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->getProductCollection();
            $collection->addFieldToFilter('entity_id', ['in' => $product]);
            foreach ($rules as $rule) {
                foreach ($collection as $item) {
                    if ($gifts = $this->getFreeGiftData($item, $rule)) {
                        $freeGits[] = $gifts;
                    }
                }
            }
            $this->saveProductGiftMulti($freeGits, true);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function processFreeGift()
    {
        $data     = [];
        $rules    = $this->getRuleCollection();
        $products = $this->getProductCollection();
        $this->truncateProductGift();
        foreach ($rules as $rule) {
            foreach ($products as $product) {
                if ($freeGits = $this->getFreeGiftData($product, $rule)) {
                    $data[] = $freeGits;
                }
            }
        }

        $this->saveProductGiftMulti($data);
        return $this;
    }

    /**
     * @param Product $product
     * @param Rule    $rule
     *
     * @return array|null
     */
    protected function getFreeGiftData(Product $product, Rule $rule)
    {
        try {
            if ($rule->getData('product_gifts')) {
                $this->ruleResolver->getFreeGiftRule($rule);
            }

            if ($this->isProductFreeGift($product, $rule)) {
                $freeGits = $rule->getData('product_gifts');
                if (empty($freeGits)) {
                    $amPromo  = $rule->getData('ampromo_rule');
                    $freeGits = $amPromo->getData('sku');
                }

                if ($freeGits && is_array($freeGits)) {
                    $freeGits = implode(',', $freeGits);
                }

                return [
                    'rule_id'       => $rule->getId(),
                    'product_id'    => $product->getId(),
                    'product_gifts' => $freeGits
                ];
            }
        } catch (Exception $exception) {
            $this->debug($exception->getMessage());
        }

        return null;
    }

    /**
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @return string
     */
    protected function getMainTable()
    {
        return $this->getConnection()->getTableName(ProductGift::CACHE_TAG);
    }

    /**
     * @return $this
     */
    private function truncateProductGift()
    {
        $connection = $this->getConnection();
        $connection->truncateTable($this->getMainTable());
        return $this;
    }

    /**
     * @param array $data
     * @param false $clean
     *
     * @return $this
     */
    public function saveProductGiftMulti(array $data = [], $clean = false)
    {
        if (empty($data)) {
            return $this;
        }

        $connection = $this->getConnection();
        $mainTable  = $this->getMainTable();
        if ($clean) {
            $bind = [];
            foreach ($data as $item) {
                $bind[] = sprintf('product_id = %d AND rule_id = %d', $item['product_id'], $item['rule_id']);
            }

            $where = implode(' ', $bind);
            $connection->delete($mainTable, $where);
        }

        $connection->insertMultiple(
            $mainTable,
            $data
        );

        return $this;
    }

    /**
     * @param $productId
     *
     * @return $this
     */
    public function deleteByProductId($productId)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();
        $mainTable  = $this->getMainTable();
        $connection->delete($mainTable, ['product_id = ?' => $productId]);
        return $this;
    }

    /**
     * @param $ruleId
     *
     * @return $this
     */
    public function deleteByRuleId($ruleId)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();
        $mainTable  = $this->getMainTable();
        $connection->delete($mainTable, ['rule_id = ?' => $ruleId]);
        return $this;
    }

    /**
     * @param Product $product
     * @param Rule    $rule
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isProductFreeGift(Product $product, Rule $rule)
    {
        $amPromo = $rule->getData('ampromo_rule');
        if (!$amPromo || empty($amPromo->getSku())) {
            return false;
        }

        $quote     = $this->quoteFactory->create();
        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );
        $qty       = $stockItem->getQty();
        if ($stockItem->getMaxSaleQty()) {
            $qty = min($stockItem->getQty(), $stockItem->getMaxSaleQty());
        }

        $requestInfo = new DataObject(['qty' => $qty]);
        $this->prepareForCart($product, $requestInfo);
        $quote->removeAllItems();
        $quote->addProduct($product, $requestInfo);
        return $rule->validate($quote);
    }

    /**
     * @return Collection
     */
    protected function getRuleCollection()
    {
        $currentTime = $this->timezone->date();
        $collection  = $this->ruleCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter(
            'from_date',
            [
                ['null' => true],
                ['lteq' => $currentTime]
            ]
        )->addFieldToFilter(
            'to_date',
            [
                ['null' => true],
                ['gteq' => $currentTime]
            ]
        );
        $collection->setOrder('sort_order', 'desc');
        $collection->getSelect()->join(
            ['ampromo' => $collection->getTable('amasty_ampromo_rule')],
            'ampromo.salesrule_id = main_table.rule_id AND ampromo.type = 1',
            ['product_gifts' => 'ampromo.sku']
        );
        return $collection;
    }

    /**
     * @return Collection
     */
    public function getProductCollection()
    {
        $categoryIds = $this->getCategoryIds();

        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        if (is_array($categoryIds)) {
            $collection->addCategoriesFilter(['in' => $categoryIds]);
        }

        return $collection;
    }

    /**
     * @return array|null
     */
    public function getCategoryIds()
    {
        $option = $this->scopeConfig->isSetFlag(self::XML_CONFIG_PATH . 'option');
        $ids    = $this->scopeConfig->getValue(self::XML_CONFIG_PATH . 'categories');
        if ($option && $ids) {
            return explode(',', $ids);
        }

        return null;
    }

    /**
     * @param Product    $product
     * @param DataObject $requestInfo
     *
     * @return $this
     */
    protected function prepareForCart(Product $product, DataObject $requestInfo)
    {
        if ($product->getTypeId() === 'simple') {
            if ($product->hasCustomOptions()) {
                $options = [];
                /** @var $option Option */
                foreach ($product->getOptions() as $option) {
                    $value                     = key($option->getValues());
                    $options[$option->getId()] = $value;
                }

                $requestInfo->setData('options', $options);
            }
        } elseif ($product->getTypeId() === 'configurable') {
            $superAttribute      = [];
            $configurableOptions = $product->getExtensionAttributes()->getConfigurableProductOptions();
            foreach ($configurableOptions as $attribute) {
                $superAttribute[$attribute->getOptionId()] = (string)$attribute->getOptionValue();
            }

            $requestInfo->setData('super_attribute', $superAttribute);
        } elseif ($product->getTypeId() === 'downloadable') {
            $links = $product->getProductLinks();
            if (!empty($links)) {
                $linkIds = [];
                foreach ($links as $link) {
                    $linkIds[] = $link->getId();
                }
                $requestInfo->setData('links', $linkIds);
            }
        } elseif ($product->getTypeId() === 'bundle') {
            $bundleOptions    = [];
            $bundleOptionsQty = [];
            //Load options
            $typeInstance = $product->getTypeInstance();
            $typeInstance->setStoreFilter($product->getStoreId(), $product);
            $optionCollection = $typeInstance->getOptionsCollection($product);
            /** @var $option \Magento\Bundle\Model\Option */
            foreach ($optionCollection as $option) {
                $selectionsCollection = $typeInstance->getSelectionsCollection([$option->getId()], $product);
                if ($option->isMultiSelection()) {
                    $bundleOptions[$option->getId()] = array_column($selectionsCollection->toArray(), 'selection_id');
                } else {
                    $bundleOptions[$option->getId()] = $selectionsCollection->getFirstItem()->getSelectionId();
                }
                $bundleOptionsQty[$option->getId()] = 1;
            }
            $requestInfo->setData('bundle_option', $bundleOptions);
            $requestInfo->setData('bundle_option_qty', $bundleOptionsQty);
        }

        $product->getTypeInstance()->prepareForCart($requestInfo, $product);
        return $this;
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function debug($message)
    {
        if (is_array($message)) {
            $message = implode(', ', $message);
        }

        $this->_logger->debug($message);
        return $this;
    }
}

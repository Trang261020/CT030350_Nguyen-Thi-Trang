<?php

namespace Ecommage\ProductIndexFreeGift\Helper;

use Ecommage\ProductIndexFreeGift\Model\ProductGiftFactory;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Ecommage\ProductIndexFreeGift\Model\ProductGift;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Data
 *
 * @package Ecommage\ProductIndexFreeGift\Helper
 */
class Data extends AbstractHelper
{
    const XML_CONFIG_PATH = 'cataloginventory/gift_options/';
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;
    /**
     * @var \Amasty\Promo\Model\Rule\Action\Discount\Product
     */
    protected $discountProduct;
    /**
     * @var ProductGiftFactory
     */
    protected $productGiftFactory;
    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $helperProduct;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Amasty\Promo\Helper\Data
     */
    protected $helperPromo;
    /**
     * @var State
     */
    protected $appState;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var null
     */
    private $output = null;

    /**
     * Data constructor.
     *
     * @param CollectionFactory                                             $productCollectionFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param ProductGiftFactory                                            $productGiftFactory
     * @param \Amasty\Promo\Model\Rule\Action\Discount\Product              $discountProduct
     * @param StoreManagerInterface                                         $storeManager
     * @param GetSalableQuantityDataBySku                                   $getSalableQuantityDataBySku
     * @param DateTime                                                      $dateTime
     * @param QuoteFactory                                                  $quoteFactory
     * @param \Magento\Catalog\Helper\Product                               $helperProduct
     * @param State                                                         $appState
     * @param \Amasty\Promo\Helper\Data                                     $helperPromo
     * @param Cart                                                          $cart
     * @param Context                                                       $context
     */
    public function __construct(
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        CollectionFactory $productCollectionFactory,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        ProductGiftFactory $productGiftFactory,
        \Amasty\Promo\Model\Rule\Action\Discount\Product $discountProduct,
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        QuoteFactory $quoteFactory,
        \Magento\Catalog\Helper\Product $helperProduct,
        State $appState,
        \Amasty\Promo\Helper\Data $helperPromo,
        Cart $cart,
        Context $context
    ) {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->productCollectionFactory    = $productCollectionFactory;
        $this->ruleCollectionFactory       = $ruleCollectionFactory;
        $this->productGiftFactory          = $productGiftFactory;
        $this->discountProduct             = $discountProduct;
        $this->helperProduct               = $helperProduct;
        $this->storeManager                = $storeManager;
        $this->quoteFactory                = $quoteFactory;
        $this->helperPromo                 = $helperPromo;
        $this->dateTime                    = $dateTime;
        $this->appState                    = $appState;
        $this->cart                        = $cart;
        parent::__construct($context);
    }

    /**
     * @param $out
     *
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param string $msg
     *
     * @return $this
     */
    public function showMessage(string $msg)
    {
        if ($this->output instanceof OutputInterface) {
            $this->output->writeln($msg);
        }

        return $this;
    }

    /**
     * @param $ids
     *
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    public function getRuleCollection($ids)
    {
        $ruleIds = (array)$ids;
        if (is_string($ids) && strpos($ids, ',') !== false) {
            $ruleIds = explode(',', $ids);
        }

        $collection = $this->ruleCollectionFactory->create();
        $collection->addFieldToFilter('rule_id', ['in' => $ruleIds]);
        return $collection;
    }

    /**
     * @throws LocalizedException
     */
    public function reIndexProductFreeGift()
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
    public function executeList(array $ids)
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
    public function reIndex($product)
    {
        $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'updateFreeGift'],
            ['product' => $product]
        );
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    public function updateFreeGift($data)
    {
        $product = $data['product'] ?? null;
        if ($product instanceof Product) {
            $freeGits = $this->updateAttributeProductFreeGifts($product);
            $this->saveProductGift($product->getId(), $freeGits);
        }

        if (is_array($product)) {
            /** @var Collection $collection */
            $collection = $this->getProductCollection();
            $collection->addFieldToFilter('entity_id', ['in' => $product]);
            foreach ($collection as $item) {
                $freeGits = $this->updateAttributeProductFreeGifts($product);
                $this->saveProductGift($product->getId(), $freeGits);
            }
        }
    }

    /**
     *
     */
    public function processFreeGift()
    {
        $data     = [];
        $products = $this->getProductCollection();
        foreach ($products as $product) {
            $freeGits = $this->updateAttributeProductFreeGifts($product);
            $data[]   = [
                'product_id'    => $product->getId(),
                'product_gifts' => implode(',', $freeGits)
            ];
        }

        $this->saveProductGiftMulti($data);
    }

    /**
     * @return array|null
     */
    public function getCategoryIds()
    {
        $option = $this->scopeConfig->isSetFlag(self::XML_CONFIG_PATH . 'option');
        $ids = $this->scopeConfig->getValue(self::XML_CONFIG_PATH . 'categories');
        if ($option && $ids) {
            return explode(',', $ids);
        }

        return null;
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
     * @param $product
     *
     * @return array
     * @throws Exception
     */
    public function updateAttributeProductFreeGifts($product)
    {
        $freeGifts = [];
        $stockInfo = $this->getSalableQuantityDataBySku->execute($product->getSku());
        $qty       = !empty($stockInfo[0]) ? $stockInfo[0]['qty'] : 0;
        //$this->showMessage(sprintf('processing product id: %s qty %d', $product->getSku(), $qty));
        $quote = $this->createQuote($product, $qty);
        if (!$quote) {
            return $freeGifts;
        }
        /** @var Item $quoteItem */
        $quoteItem      = $quote->getItemsCollection()->getFirstItem();
        $appliedRuleIds = $quote->getAppliedRuleIds();
        //$this->showMessage(sprintf('Add Product ID: %s qty: %d to quote ID: %d quote item ID: %d', $quoteItem->getProductId(), $quoteItem->getQty(), $quoteItem->getQuoteId(), $quoteItem->getId()));
        if (!$quoteItem->getItemId() || empty($appliedRuleIds)) {
            return $freeGifts;
        }

        $rules = $this->getRuleCollection($appliedRuleIds);
        foreach ($rules as $rule) {
            $this->discountProduct->calculate($rule, $quoteItem, 1);
            $products = $this->helperPromo->getNewItems();
            if ($products) {
                foreach ($products as $freeGift) {
                    $freeGifts[] = $freeGift->getId();
                }
            }
        }
        $quote->delete();
        $freeGifts = array_unique($freeGifts);
        return $freeGifts;
    }

    /**
     * @param       $productId
     * @param array $freeGifts
     */
    public function saveProductGift($productId, array $freeGifts = [])
    {
        $productGifts = null;
        if (!empty($freeGifts)) {
            $productGifts = implode(',', $freeGifts);
        }

        /** @var ProductGift $productGift */
        $productGift = $this->productGiftFactory->create();
        $productGift->load($productId, 'product_id');
        $productGift->addData(
            [
                'product_id'    => $productId,
                'product_gifts' => $productGifts
            ]
        )->save();
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function saveProductGiftMulti(array $data = [])
    {
        /** @var \Ecommage\ProductIndexFreeGift\Model\ResourceModel\ProductGift\Collection $collection */
        $collection = $this->productGiftFactory->create()->getCollection();
        /** @var AdapterInterface $connection */
        $connection = $collection->getConnection();
        $mainTable  = $collection->getMainTable();
        $connection->truncateTable($mainTable);
        if (empty($data)) {
            return $this;
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
     * @throws Exception
     */
    public function delete($productId)
    {
        $productGift = $this->productGiftFactory->create();
        $productGift->load($productId, 'product_id');
        if ($productGift->getId()) {
            $productGift->delete();
        }
    }

    /**
     * @param $product
     * @param $qty
     *
     * @return Quote|null
     */
    public function createQuote($product, $qty)
    {
        if (!$product->isSaleable()) {
            return null;
        }

        $requestData = [
            'qty'     => $qty,
            'product' => $product->getId(),
            'item'    => $product->getId()
        ];

        if ($product->getTypeId() == 'configurable') {
            return $this->getAddConfigurableProduct($product);
        }

        try {
            $quote = $this->quoteFactory->create();
            $this->cart->setQuote($quote);
            $this->cart->addProduct($product, $requestData);
            $this->cart->getQuote()->setIsActive(0);
            $this->cart->save();
        } catch (Exception $exception) {
            $this->_logger->info($exception->getMessage());
        }

        return $this->cart->getQuote();
    }

    /**
     * @param $parent
     */
    public function getAddConfigurableProduct($parent)
    {
        $quote = $this->quoteFactory->create();
        $this->cart->setQuote($quote);
        $children = $parent->getTypeInstance()->getUsedProducts($parent);
        foreach ($children as $child) {
            $options = [];
            $qty     = $this->getSalableQuantityDataBySku->execute($child->getSku());
            //init request add cart
            $requestInfo             = [
                'product' => $parent->getId(),
                'qty'     => $qty
            ];
            $productAttributeOptions = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
            foreach ($productAttributeOptions as $option) {
                $options[$option['attribute_id']] = $child->getData($option['attribute_code']);
            }
            $requestInfo['super_attribute'] = $options;
            $this->cart->addProduct($parent, $requestInfo);
        }
        $this->cart->getQuote()->setIsActive(0);
        $this->cart->save();
        return $this->cart->getQuote();
    }

    /**
     * @param $msg
     *
     * @return $this
     */
    public function debug($msg)
    {
        $this->_logger->debug($msg);
        return $this;
    }
}

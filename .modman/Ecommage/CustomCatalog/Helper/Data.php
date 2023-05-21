<?php

namespace Ecommage\CustomCatalog\Helper;

use Magento\Customer\Model\SessionFactory as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\UrlInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Class Data
 *
 * @package Ecommage\CustomCatalog\Helper
 */
class Data extends AbstractHelper
{
    const BUYNOW_PATH       = 'buynow/';
    const ADDTOCART_FORM_ID = 'ADDTOCART_FORM_ID';
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var ItemFactory
     */
    protected $_wishlistItemFactory;
    /**
     * @var Conditions
     */
    protected $conditions;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Category
     */
    protected $_category;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;
    /**
     * @var Product
     */
    protected $_product;
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var UrlInterface
     */
    protected $url;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;
    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * Data constructor.
     * @param Conditions $conditions
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param Context $context
     * @param Registry $_coreRegistry
     * @param ItemFactory $_wishlistItemFactory
     * @param CustomerSession $customerSession
     * @param Category $category
     * @param StoreManagerInterface $storeManagerInterface
     * @param Product $product
     * @param ProductRepository $productRepository
     * @param UrlInterface $url
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param Attribute $attribute
     */
    public function __construct(
        Conditions $conditions,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        Context $context,
        Registry $_coreRegistry,
        ItemFactory $_wishlistItemFactory,
        CustomerSession $customerSession,
        Category $category,
        StoreManagerInterface $storeManagerInterface,
        Product $product,
        ProductRepository $productRepository,
        UrlInterface $url,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        Attribute $attribute
    ) {
        $this->conditions           = $conditions;
        $this->categoryRepository   = $categoryRepository;
        $this->storeManager         = $storeManager;
        $this->categoryFactory      = $categoryFactory;
        $this->_coreRegistry        = $_coreRegistry;
        $this->_wishlistItemFactory = $_wishlistItemFactory;
        $this->customerSession      = $customerSession;
        $this->_category = $category;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_product = $product;
        $this->_productRepository = $productRepository;
        $this->url = $url;
        $this->request = $context->getRequest();
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->attribute = $attribute;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getAddToCartFormId()
    {
        $addToCartFormId = $this->getBuyNowConfig('general/addtocart_id');
        return $addToCartFormId ? $addToCartFormId : self::ADDTOCART_FORM_ID;
    }

    /**
     * @param      $path
     * @param null $storeId
     *
     * @return mixed
     */
    public function getBuyNowConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::BUYNOW_PATH . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return mixed
     */
    public function keepCartProducts()
    {
        return $this->getBuyNowConfig('general/keep_cart_products');
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @param $categoryId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryUrl($categoryId)
    {
        $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
        if ($category->getId()) {
            return $category->getUrl();
        }
        return '';
    }

    /**
     * @return mixed|null
     */
    public function getCurrentProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId(){
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @param $id
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed|null
     * @throws NoSuchEntityException
     */
    public function getProduct($id)
    {
        return $this->_productRepository->getById($id);
    }

    /**
     * @param $product
     * @return bool
     */
    public function checkQtyAndStockOptionProduct($product){
        if($product && $product->getTypeId() == "bundle"){
            $selectionCollection = $product->getTypeInstance()
                ->getSelectionsCollection(
                    $product->getTypeInstance()->getOptionsIds($product),
                    $product
                );
            foreach ($selectionCollection as $selection) {
                $productRepository = $this->_productRepository->get($selection['sku']);
                $salable = $this->getSalableQuantityDataBySku->execute($selection['sku']);

                $qtyAndStock = $productRepository->getQuantityAndStockStatus();

                if($qtyAndStock){
                    if($qtyAndStock['is_in_stock']){
                        if($salable[0]['qty'] <= 0){
                            return false;
                        }
                    }else{
                        return false;
                    }
                }
            }

        }
        if($product && $product->getTypeId() == "simple"){
            $salable = $this->getSalableQuantityDataBySku->execute($product->getSku());
            $productRepository = $this->_productRepository->get($product->getSku());
            $qtyAndStock = $productRepository->getQuantityAndStockStatus();

            if(isset($qtyAndStock['is_in_stock']) && $qtyAndStock['is_in_stock']){
                if($product->getPrice() == 0){
                    return false;
                }
                if($salable[0]['qty'] <= 0 && $product->getPrice() == 0){
                    return false;
                }
            }else{
                return false;
            }
        }

        return true;
    }
    public function isCheckLogin()
    {
        $customer = $this->customerSession->create();
        if($customer->isLoggedIn()){
            return true;
        }
        return  false;
    }
}

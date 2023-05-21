<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_PromoCountdown
 */


namespace Ecommage\CatalogPromoCountdown\Block\Widgets;

use Amasty\PromoCountdown\Model\ConfigProvider;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Widget\Html\Pager;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\EncoderInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Widget\Block\BlockInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule;

class Countdown extends AbstractProduct implements BlockInterface, IdentityInterface
{
    const API_METHOD = 'rest';
    const API_VERSION = 'V1';
    const API_PATH = 'promo-countdown/service/date/difference';
    const DESIGN_BASE_PATH = 'Amasty_PromoCountdown/js/design/';
    const DESIGN_BASE_COMPONENT = 'uiComponent';
    /**
     * Default value for products per page
     */
    const DEFAULT_PRODUCTS_PER_PAGE = 5;

    /**
     * Default value whether show pager or not
     */
    const DEFAULT_SHOW_PAGER = false;

    /**
     * Default value for products count that will be shown
     */
    const DEFAULT_PRODUCTS_COUNT = 10;

    /**
     * Threshold qty config path
     * @deprecated
     * @see \Magento\CatalogInventory\Model\Configuration::XML_PATH_STOCK_THRESHOLD_QTY
     */
    const XML_PATH_STOCK_THRESHOLD_QTY = 'cataloginventory/options/stock_threshold_qty';

    /**
     * @var string
     * @codingStandardsIgnoreStart
     */
    protected $_template = "Ecommage_CatalogPromoCountdown::products/promo/countdown.phtml";
    //@codingStandardsIgnoreEnd
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var EncoderInterface|null
     */
    private $urlEncoder;

    /**
     * Instance of pager block
     *
     * @var Pager
     */
    protected $pager;

    /**
     * @var HttpContext
     */
    protected $httpContext;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var Product\Visibility
     */
    protected $catalogProductVisibility;

    /**
     * Countdown constructor.
     * @param Json|null $json
     * @param Context $context
     * @param DateTime $dateTime
     * @param HttpContext $httpContext
     * @param RuleFactory $ruleFactory
     * @param ConfigProvider $configProvider
     * @param LayoutFactory|null $layoutFactory
     * @param EncoderInterface|null $urlEncoder
     * @param Product\Visibility $catalogProductVisibility
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Json $json = null,
        Context $context,
        DateTime $dateTime,
        HttpContext $httpContext,
        RuleFactory $ruleFactory,
        ConfigProvider $configProvider,
        LayoutFactory $layoutFactory = null,
        EncoderInterface $urlEncoder = null,
        Product\Visibility $catalogProductVisibility,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
        $this->httpContext = $httpContext;
        $this->ruleFactory = $ruleFactory;
        $this->configProvider = $configProvider;
        $this->collectionFactory = $collectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->layoutFactory = $layoutFactory ?: ObjectManager::getInstance()->get(LayoutFactory::class);
        $this->urlEncoder = $urlEncoder ?: ObjectManager::getInstance()->get(EncoderInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addColumnCountLayoutDepend('empty', 6)
            ->addColumnCountLayoutDepend('1column', 5)
            ->addColumnCountLayoutDepend('2columns-left', 4)
            ->addColumnCountLayoutDepend('2columns-right', 4)
            ->addColumnCountLayoutDepend('3columns', 3);

        $this->addData([
            'cache_lifetime' => 86400,
            'cache_tags' => [
                Product::CACHE_TAG,
            ],
        ]);
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        $conditions = $this->getData('catalog_rule');
        return [
            'CATALOG_PRODUCTS_COUNTDOWN_LIST_WIDGET',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
            $this->getProductsPerPage(),
            $this->getProductsCount(),
            $conditions,
            $this->json->serialize($this->getRequest()->getParams()),
            $this->getTemplate(),
            $this->getData('text_before')
        ];
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getProductCollection()) {
            foreach ($this->getProductCollection() as $product) {
                if ($product instanceof IdentityInterface) {
                    $identities[] = $product->getIdentities();
                }
            }
        }
        $identities = array_merge([], ...$identities);

        return $identities ?: [Product::CACHE_TAG];
    }

    /**
     * Retrieve how many products should be displayed
     *
     * @return int
     */
    public function getProductsCount()
    {
        if ($this->hasData('products_count')) {
            return $this->getData('products_count');
        }

        if (null === $this->getData('products_count')) {
            $this->setData('products_count', self::DEFAULT_PRODUCTS_COUNT);
        }

        return $this->getData('products_count');
    }

    /**
     * Retrieve how many products should be displayed
     *
     * @return int
     */
    public function getProductsPerPage()
    {
        if (!$this->hasData('products_per_page')) {
            $this->setData('products_per_page', self::DEFAULT_PRODUCTS_PER_PAGE);
        }
        return $this->getData('products_per_page');
    }

    /**
     * Return flag whether pager need to be shown or not
     *
     * @return bool
     */
    public function showPager()
    {
        if (!$this->hasData('show_pager')) {
            $this->setData('show_pager', self::DEFAULT_SHOW_PAGER);
        }
        return (bool)$this->getData('show_pager');
    }

    /**
     * Retrieve how many products should be displayed on page
     *
     * @return int
     */
    protected function getPageSize()
    {
        return $this->showPager() ? $this->getProductsPerPage() : $this->getProductsCount();
    }

    /**
     * Render pagination HTML
     *
     * @return string
     * @throws LocalizedException
     */
    public function getPagerHtml()
    {
        if ($this->showPager() && $this->getProductCollection()->getSize() > $this->getProductsPerPage()) {
            if (!$this->pager) {
                $this->pager = $this->getLayout()->createBlock(
                    Pager::class,
                    $this->getWidgetPagerBlockName()
                );

                $this->pager->setUseContainer(true)
                    ->setShowAmounts(true)
                    ->setShowPerPage(false)
                    ->setPageVarName($this->getData('page_var_name'))
                    ->setLimit($this->getProductsPerPage())
                    ->setTotalLimit($this->getProductsCount())
                    ->setCollection($this->getProductCollection());
            }
            if ($this->pager instanceof \Magento\Framework\View\Element\AbstractBlock) {
                return $this->pager->toHtml();
            }
        }
        return '';
    }

    /**
     * Get currency of product
     *
     * @return PriceCurrencyInterface
     * @deprecated 100.2.0
     */
    private function getPriceCurrency()
    {
        if ($this->priceCurrency === null) {
            $this->priceCurrency = ObjectManager::getInstance()
                ->get(PriceCurrencyInterface::class);
        }
        return $this->priceCurrency;
    }

    /**
     * @inheritdoc
     */
    public function getAddToCartUrl($product, $additional = [])
    {
        $requestingPageUrl = $this->getRequest()->getParam('requesting_page_url');

        if (!empty($requestingPageUrl)) {
            $additional['useUencPlaceholder'] = true;
            $url = parent::getAddToCartUrl($product, $additional);
            return str_replace('%25uenc%25', $this->urlEncoder->encode($requestingPageUrl), $url);
        }

        return parent::getAddToCartUrl($product, $additional);
    }

    /**
     * Get widget block name
     *
     * @return string
     */
    private function getWidgetPagerBlockName()
    {
        $pageName = $this->getData('page_var_name');
        $pagerBlockName = 'widget.products.list.pager';

        if (!$pageName) {
            return $pagerBlockName;
        }

        return $pagerBlockName . '.' . $pageName;
    }

    /**
     * @return mixed
     */
    public function getCatalogRule()
    {
        return $this->getData('catalog_rule');
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = $renderZone;
        }
        $arguments['price_id'] = isset($arguments['price_id'])
            ? $arguments['price_id']
            : 'old-price-' . $product->getId() . '-' . $priceType;
        $arguments['include_container'] = isset($arguments['include_container'])
            ? $arguments['include_container']
            : true;
        $arguments['display_minimal_price'] = isset($arguments['display_minimal_price'])
            ? $arguments['display_minimal_price']
            : true;

        /** @var \Magento\Framework\Pricing\Render $priceRender */
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                \Magento\Framework\Pricing\Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        $price = $priceRender->render(
            FinalPrice::PRICE_CODE,
            $product,
            $arguments
        );

        return $price;
    }

    /**
     * @inheritdoc
     */
    protected function getDetailsRendererList()
    {
        if (empty($this->rendererListBlock)) {
            /** @var $layout LayoutInterface */
            $layout = $this->layoutFactory->create(['cacheable' => false]);
            $layout->getUpdate()->addHandle('catalog_widget_product_list')->load();
            $layout->generateXml();
            $layout->generateElements();

            $this->rendererListBlock = $layout->getBlock('category.product.type.widget.details.renderers');
        }
        return $this->rendererListBlock;
    }

    /**
     * Get post parameters.
     *
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        /** @var Rule  $catalogRuleFactory */
        $catalogRuleFactory = $this->ruleFactory->create()->load((int)$this->getCatalogRule());
        $catalogRuleFactory->setWebsiteIds("1");
        $productIdsArray = $catalogRuleFactory->getMatchingProductIds();

        /** @var $collection Collection */
        $collection = $this->collectionFactory->create();
        if ($this->getData('store_id') !== null) {
            $collection->setStoreId($this->getData('store_id'));
        }
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        /**
         * Change sorting attribute to entity_id because created_at can be the same for products fastly created
         * one by one and sorting by created_at is indeterministic in this case.
         */
        $collection->getSelect()->joinLeft(array('st' => 'inventory_stock_1'),
            'e.entity_id = st.product_id');
        $collection->getSelect()->order('st.is_salable DESC');

        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->addAttributeToSort('entity_id', 'desc')
            ->addAttributeToFilter('entity_id', ['in' => array_keys($productIdsArray)])
            ->setPageSize($this->getPageSize())
            ->setCurPage($this->getRequest()->getParam($this->getData('page_var_name'), 1));;

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);

        return $collection;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTemplate()
    {
        if ($this->configProvider->isModuleEnable($this->_storeManager->getStore()->getId())) {
            return parent::getTemplate();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getDesignComponent()
    {
        $design = $this->getData('design');

        if (in_array($design, \Amasty\PromoCountdown\Model\Config\Design::ANIMATED_DESIGNS)) {
            return self::DESIGN_BASE_PATH . $design;
        }

        return self::DESIGN_BASE_COMPONENT;
    }

    /**
     * @param string $hexColor
     * @param int $percent
     *
     * @return string
     */
    public function luminance($hexColor, $percent)
    {
        $hexColor = array_map('hexdec', str_split(str_pad(str_replace('#', '', $hexColor), 6, '0'), 2));

        foreach ($hexColor as $i => $color) {
            $from = $color;
            $to = 255;

            if ($percent < 0) {
                $from = 0;
                $to = $color;
            }

            $pvalue = ceil(($to - $from) * $percent / 100);
            $hexColor[$i] = str_pad(dechex($color + $pvalue), 2, '0', STR_PAD_LEFT);
        }

        return '#' . implode($hexColor);
    }

    /**
     * @return string
     */
    public function getPostfix()
    {
        return $this->getJsId($this->getStartTime(), $this->getTargetTime(), $this->getData('backgroundColor'));
    }

    /**
     * @return int
     */
    public function getTargetTime()
    {
        return $this->dateTime->gmtTimestamp($this->getData('date_to'));
    }

    /**
     * @return bool
     */
    public function getStatusDisplay(){
        if ($this->timezone->scopeTimeStamp() >= $this->getTargetTime()) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->dateTime->gmtTimestamp($this->getData('date_from'));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getServiceUrl()
    {
        $data = [
            self::API_METHOD,
            $this->_storeManager->getStore()->getCode(),
            self::API_VERSION,
            self::API_PATH
        ];

        return $this->getUrl(implode('/', $data));
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getStockQty($product)
    {
        $qty = 0;
        $productId = $product->getId();
        if ($productId) {
            $qty = $this->getProductStockQty($product);
        }
        return $qty;
    }

    /**
     * Retrieve product stock qty
     *
     * @param Product $product
     * @return float
     */
    public function getProductStockQty($product)
    {
        return $this->stockRegistry->getStockStatus($product->getId(), $product->getStore()->getWebsiteId())->getQty();
    }

    /**
     * Retrieve threshold of qty to display stock qty message
     *
     * @return string
     */
    public function getThresholdQty()
    {
        if (!$this->hasData('threshold_qty')) {
            $qty = (float)$this->_scopeConfig->getValue(
                self::XML_PATH_STOCK_THRESHOLD_QTY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $this->setData('threshold_qty', $qty);
        }

        return $this->getData('threshold_qty');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isMsgVisible($product)
    {
        return $this->getStockQty($product) > 0
            && $this->getStockQtyLeft($product) > 0
            && $this->getStockQtyLeft($product) <= $this->getThresholdQty();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getStockQtyLeft($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $minStockQty = $stockItem->getMinQty();
        return $this->getStockQty($product) - $minStockQty;
    }
}

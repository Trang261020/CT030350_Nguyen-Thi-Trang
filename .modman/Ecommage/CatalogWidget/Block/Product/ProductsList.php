<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecommage\CatalogWidget\Block\Product;

use Exception;
use Ecommage\CatalogWidget\Helper\PrepareData;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\Widget\Html\Pager;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Widget\Block\BlockInterface;

/**
 * Catalog Products List widget block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class ProductsList extends AbstractProduct implements BlockInterface, IdentityInterface
{
    /**
     * Default value for products count that will be shown
     */
    const DEFAULT_PRODUCTS_COUNT = 10;

    /**
     * Name of request parameter for page number value
     *
     * @deprecated @see $this->getData('page_var_name')
     */
    const PAGE_VAR_NAME = 'np';

    /**
     * Default value for products per page
     */
    const DEFAULT_PRODUCTS_PER_PAGE = 5;

    /**
     * Default value whether show pager or not
     */
    const DEFAULT_SHOW_PAGER = false;

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
     * Catalog product visibility
     *
     * @var Visibility
     */
    protected $catalogProductVisibility;

    /**
     * Product collection factory
     *
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

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
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var RendererList
     */
    private $rendererListBlock;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var array
     */
    protected $giftIds = [];
    /**
     *
     * @var Status
     */
    protected $catalogProductStatus;
    /**
     * @var string
     */
    protected $_template = "Ecommage_CatalogWidget::categories/product-list.phtml";

    /**
     * ProductsList constructor.
     *
     * @param Context                          $context
     * @param CollectionFactory                $productCollectionFactory
     * @param Visibility                       $catalogProductVisibility
     * @param HttpContext                      $httpContext
     * @param ProductFactory                   $productFactory
     * @param LayoutFactory|null               $layoutFactory
     * @param EncoderInterface|null            $urlEncoder
     * @param CategoryRepositoryInterface|null $categoryRepository
     * @param Status                           $catalogProductStatus
     * @param array                            $data
     * @param Json|null                        $json
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        HttpContext $httpContext,
        ProductFactory $productFactory,
        LayoutFactory $layoutFactory = null,
        EncoderInterface $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null,
        Status $catalogProductStatus,
        array $data = [],
        Json $json = null
    ) {
        $this->catalogProductStatus     = $catalogProductStatus;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->httpContext              = $httpContext;
        $this->productFactory           = $productFactory;
        $this->json                     = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->layoutFactory            = $layoutFactory ?: ObjectManager::getInstance()->get(LayoutFactory::class);
        $this->urlEncoder               = $urlEncoder ?: ObjectManager::getInstance()->get(EncoderInterface::class);
        $this->categoryRepository       = $categoryRepository ?? ObjectManager::getInstance()
                                                                              ->get(CategoryRepositoryInterface::class);
        parent::__construct(
            $context,
            $data
        );

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
        $this->addData(
            [
                'cache_lifetime' => 86400,
                'cache_tags'     => [
                    Product::CACHE_TAG,
                ],
            ]
        );
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
        return [
            $this->getCategories(),
            'CATALOG_PRODUCTS_LIST_BY_CATEGORY_WIDGET',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            (int)$this->getRequest()->getParam($this->getData('page_var_name'), 1),
            $this->getProductsPerPage(),
            $this->getProductsCount(),
            $this->getData('categories'),
            $this->json->serialize($this->getRequest()->getParams()),
            $this->getTemplate(),
            $this->getTitle()
        ];
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = $renderZone;
        }
        $arguments['price_id']              = isset($arguments['price_id'])
            ? $arguments['price_id']
            : 'old-price-' . $product->getId() . '-' . $priceType;
        $arguments['include_container']     = isset($arguments['include_container'])
            ? $arguments['include_container']
            : true;
        $arguments['display_minimal_price'] = isset($arguments['display_minimal_price'])
            ? $arguments['display_minimal_price']
            : true;

        /** @var Render $priceRender */
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                Render::class,
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
     *
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data'   => [
                'product'                               => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->getData('categories');
    }

    /**
     * @return string|null
     */
    public function getCategoryUrl()
    {
        try {
            /** @var  CategoryInterface $category */
            $category = $this->categoryRepository->get((int)$this->getCategories());
            return $category->getUrl();
        } catch (Exception $e) {
        }
        return null;
    }

    /**
     * Prepare and return product collection
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     * @throws LocalizedException
     */
    public function createCollection()
    {
        /** @var $collection Collection */
        $collection = $this->productCollectionFactory->create();

        if ($this->getData('store_id') !== null) {
            $collection->setStoreId($this->getData('store_id'));
        }
        $collection->addAttributeToFilter('status', ['in' => $this->catalogProductStatus->getVisibleStatusIds()]);
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
                           ->setPageSize($this->getPageSize())
                           ->setCurPage($this->getRequest()->getParam($this->getData('page_var_name'), 1));

        $categories = $this->getCategories();
        $collection->addCategoriesFilter(['in' => $categories]);

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);
        return $collection;
    }

    /**
     * @param Collection $collection
     *
     * @return mixed
     */
    public function ignoreGift($collection)
    {
        $productGiftIds = $this->productGiftIds();
        if (!empty($productGiftIds)) {
            return $collection->addFieldToFilter('entity_id', array('nin' => $productGiftIds));
        }
        return $collection;
    }

    /**
     * @param Collection $collection
     *
     * @return mixed
     */
    public function filterGift($collection)
    {
        $collection->getSelect()->join(
            ['g' => $collection->getTable('product_free_gifts')],
            'g.product_id = e.entity_id AND product_gifts != ""',
            ['g.product_gifts']
        )->orderRand();
        $collection->setPageSize($this->getNumberGiftDisplay());

        return $collection;
    }

    /**
     * @return array
     */
    public function productGiftIds()
    {
        return $this->giftIds;
    }

    /**
     * @param $product
     *
     * @return DataObject
     */
    public function getGiftByProduct($product)
    {
        $giftSkus = (array)$product->getProductGifts();
        if (is_string($product->getProductGifts()) && strpos($product->getProductGifts(), ',') !== false) {
            $giftSkus = explode(',', $product->getProductGifts());
        }

        /** @var $collection Collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('sku', ['in' => $giftSkus]);
        $collection->addAttributeToFilter('status', ['in' => $this->catalogProductStatus->getVisibleStatusIds()]);
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection = $this->_addProductAttributesAndPrices($collection)
                           ->addStoreFilter();
        $collection->getSelect()->orderRand();
        $gift            = $collection->getFirstItem();
        $this->giftIds[] = $product->getId();
        return $gift;
    }

    /**
     * Add all attributes and apply pricing logic to products collection
     * to get correct values in different products lists.
     * E.g. crosssells, upsells, new products, recently viewed
     *
     * @param Collection $collection
     *
     * @return Collection
     */
    protected function _addProductAttributesAndPrices(
        Collection $collection
    ) {
        return $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite();
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
        if ($this->showPager() && $this->createCollection()->getSize() > $this->getProductsPerPage()) {
            if (!$this->pager) {
                $this->pager = $this->getLayout()->createBlock(
                    Pager::class,
                    $this->getWidgetPagerBlockName()
                );

                $this->pager->setUseContainer(true)
//                    ->setShowAmounts(true)
//                    ->setShowPerPage(false)
//                    ->setPageVarName($this->getData('page_var_name'))
//                    ->setLimit($this->getProductsPerPage())
//                    ->setTotalLimit($this->getProductsCount())
                            ->setCollection($this->createCollection());
            }
            if ($this->pager instanceof AbstractBlock) {
                return $this->pager->toHtml();
            }
        }
        return '';
    }

    /**
     * do widget sử dụng dữ liệu cache sẵn từ html
     * nên không sử dụng cache default nên bỏ getIdentities
     * để tránh cho widget gọi lại các function truy vấn dữ liệu
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
//        if ($this->createCollection()) {
//            foreach ($this->createCollection() as $product) {
//                if ($product instanceof IdentityInterface) {
//                    $identities[] = $product->getIdentities();
//                }
//            }
//        }
//        $identities = array_merge([], ...$identities);

        return $identities ?: [Product::CACHE_TAG];
    }

    /**
     * Get value of widgets' title parameter
     *
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->getData('title');
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
            $url                              = parent::getAddToCartUrl($product, $additional);
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
        $pageName       = $this->getData('page_var_name');
        $pagerBlockName = 'widget.products.list.pager';

        if (!$pageName) {
            return $pagerBlockName;
        }

        return $pagerBlockName . '.' . $pageName;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function toHtml()
    {
        $html     = '';
        $filePath = $this->getModuleHtmlTemplate();
        $isRender = $this->_coreRegistry->registry(PrepareData::CONFIG_PREPARE_CONTENT);
        $writer   = $this->_filesystem->getDirectoryWrite(DirectoryList::TEMPLATE_MINIFICATION_DIR);
        $this->setBlockTemplates($filePath);
        if (!$writer->isExist($filePath) || $isRender) {
            $html = parent::toHtml();
            $writer->writeFile($filePath, $html);
        }

        if (!$html) {
            $html = $writer->readFile($filePath);
        }

        return $html;
    }

    /**
     * @param $template
     *
     * @return array
     */
    private function setBlockTemplates($template)
    {
        $templates = $this->_coreRegistry->registry(PrepareData::CONFIG_PREPARE_TEMPLATES);
        if (empty($templates)) {
            $templates = [];
        }

        $templates = array_unique(array_merge($templates, [$template]));
        $this->_coreRegistry->unregister(PrepareData::CONFIG_PREPARE_TEMPLATES);
        $this->_coreRegistry->register(PrepareData::CONFIG_PREPARE_TEMPLATES, $templates);
        return $template;
    }

    /**
     * @return false|string
     */
    private function getModuleHtmlTemplate()
    {
        $key          = sha1($this->toJson());
        $templateFile = $this->getTemplateFile();
        $paths        = explode('/', $templateFile);
        $fileName     = array_pop($paths);
        $fileHtml     = sprintf('%s.html', $key);
        $templateFile = str_replace($fileName, $fileHtml, $templateFile);
        return 'html/' . substr($templateFile, strpos($templateFile, 'Ecommage/CatalogWidget'));
    }
}

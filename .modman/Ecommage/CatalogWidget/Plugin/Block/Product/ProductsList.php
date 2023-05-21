<?php
/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecommage\CatalogWidget\Plugin\Block\Product;

use Closure;
use Ecommage\CatalogWidget\Helper\PrepareData;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Filesystem;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;

class ProductsList extends \Magento\CatalogWidget\Block\Product\ProductsList
{

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var Conditions
     */
    protected $conditionsHelper;
    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * @var SqlBuilder
     */
    protected $sqlBuilder;

    /**
     * Product collection factory
     *
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Catalog product visibility
     *
     * @var Visibility
     */
    protected $catalogProductVisibility;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * ProductsList constructor.
     *
     * @param Filesystem $filesystem
     * @param Registry   $coreRegistry
     */
    public function __construct(
        Filesystem $filesystem,
        Registry $coreRegistry,
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        HttpContext $httpContext,
        SqlBuilder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        array $data = [],
        Json $json = null,
        LayoutFactory $layoutFactory = null,
        EncoderInterface $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->conditionsHelper = $conditionsHelper;
        $this->rule = $rule;
        $this->_catalogConfig = $context->getCatalogConfig();
        $this->sqlBuilder = $sqlBuilder;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_filesystem   = $filesystem;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $productCollectionFactory, $catalogProductVisibility, $httpContext, $sqlBuilder, $rule, $conditionsHelper);
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundToHtml($subject, Closure $proceed)
    {
        $filePath = $this->getModuleHtmlTemplate($subject);
        $isRender = $this->_coreRegistry->registry(PrepareData::CONFIG_PREPARE_CONTENT);
        $writer   = $this->_filesystem->getDirectoryWrite(DirectoryList::TEMPLATE_MINIFICATION_DIR);
        $this->setBlockTemplates($filePath);
        if (!$isRender && $writer->isExist($filePath)) {
            return $writer->readFile($filePath);
        }

        $html = $proceed();
        $writer->writeFile($filePath, $html);
        return $html;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetIdentities($subject, Closure $proceed)
    {
        return [];
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
     * @param $subject
     *
     * @return string
     */
    private function getModuleHtmlTemplate($subject)
    {
        $key          = sha1($subject->toJson());
        $templateFile = $subject->getTemplateFile();
        $paths        = explode('/', $templateFile);
        $fileName     = array_pop($paths);
        $fileHtml     = sprintf('%s.html', $key);
        $templateFile = str_replace($fileName, $fileHtml, $templateFile);
        list($vendor, $module) = explode('\\',get_class($subject));
        $cutPosition = strpos($templateFile, '/templates/');
        $template = sprintf('%s/%s/%s', $vendor, $module, ltrim(substr($templateFile, $cutPosition),'/'));
        return 'html/' . $template;
    }

    /**
     * @param \Magento\CatalogWidget\Block\Product\ProductsList $subject
     * @param callable $proceed
     * @param $result
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundCreateCollection(\Magento\CatalogWidget\Block\Product\ProductsList $subject, callable $proceed)
    {
        /** @var $collection Collection */
        $collection = $this->productCollectionFactory->create();
        if ($subject->getData('store_id') !== null) {
            $collection->setStoreId($subject->getData('store_id'));
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
            ->setPageSize($this->getPageSizes($subject->getShowPerpage(),$subject->getProductsPerPage(),$subject->getProductsCount()))
            ->setCurPage($subject->getRequest()->getParam($subject->getData('page_var_name'), 1));

        /**
         * Prevent retrieval of duplicate records. This may occur when multiselect product attribute matches
         * several allowed values from condition simultaneously
         */
        $collection->distinct(true);

        return $collection;
    }

    /**
     * Retrieve how many products should be displayed on page
     *
     * @return int
     */
    protected function getPageSizes($perpage,$proPerpage,$proCount)
    {
        return $perpage ? $proPerpage : $proCount;
    }
}

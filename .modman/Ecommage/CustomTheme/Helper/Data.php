<?php

namespace Ecommage\CustomTheme\Helper;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;

/**
 * Class Data
 *
 * @package Ecommage\CustomTheme\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * Category factory
     *
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Data constructor.
     *
     * @param Repository      $assetRepo
     * @param CategoryFactory $categoryFactory
     * @param Context         $context
     */
    public function __construct(
        Repository $assetRepo,
        CategoryFactory $categoryFactory,
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo       = $assetRepo;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context);
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getImageFullPath()
    {
        $fileId = 'svg/svg-sprites.svg';
        $params = [
            'area' => 'frontend' //for admin area its backend
        ];
        $asset  = $this->assetRepo->createAsset($fileId, $params);
        return $asset->getSourceFile();
    }

    /**
     * @param $catId
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function loadCategory($catId)
    {
        return $this->categoryFactory->create()->load($catId);
    }

    /**
     * @param $oldClass
     *
     * @return string
     */
    public function getItemCSSClass($htmlChild, $oldClass)
    {
        if (strpos($htmlChild, 'level1') === false) {
            return str_replace('parent','', $oldClass);
        }

        return $oldClass;
    }

    /**
     * @return mixed
     */
    public function getDisplayCatFilterConfig(){
        return $this->scopeConfig->getValue('catalog/general/is_display_filter_category',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}

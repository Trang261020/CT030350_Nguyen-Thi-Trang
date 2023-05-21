<?php

namespace Ecommage\CustomCatalog\Helper;

use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Amasty\ShopbyBrand\Helper\Data;

/**
 * Class Brand
 *
 * @package Ecommage\CustomCatalog\Helper
 */
class Brand extends AbstractHelper
{
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Brand constructor.
     *
     * @param Repository $repository
     * @param Data       $helperData
     * @param Context    $context
     */
    public function __construct(
        Repository $repository,
        Data $helperData,
        Context $context
    ) {
        $this->helperData = $helperData;
        $this->repository = $repository;
        parent::__construct($context);
    }

    /**
     * @param $brandValue
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getbrandUrl($brandValue)
    {
        $brandCode = $this->getBrandCode();
        $options = $this->repository->get($brandCode)->getOptions();
        foreach ($options as $option) {
            if ($option->getValue() == $brandValue) {
                return $this->helperData->getBrandUrl($option);
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getBrandCode()
    {
        return $this->scopeConfig->getValue('amshopby_brand/general/attribute_code');
    }
}

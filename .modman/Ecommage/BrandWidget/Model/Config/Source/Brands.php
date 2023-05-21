<?php

namespace Ecommage\BrandWidget\Model\Config\Source;

use Amasty\ShopbyBrand\Model\ConfigProvider;
use Amasty\ShopbyBrand\Model\ResourceModel\Slider\Grid\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class Brands implements OptionSourceInterface
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     *
     */
    protected $options = null;

    /**
     * Brands constructor.
     *
     * @param Collection     $collection
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Collection $collection,
        ConfigProvider $configProvider
    ) {
        $this->collection = $collection;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $attributeCode = $this->configProvider->getAllBrandAttributeCodes();
            $collection = $this->collection->addFieldToFilter('attribute_code', $attributeCode);
            foreach ($collection as $brand) {
                $this->options[] = [
                  'value' => $brand->getOptionId(),
                  'label' => $brand->getTitle()
                ];
            }
        }

        return $this->options;
    }
}

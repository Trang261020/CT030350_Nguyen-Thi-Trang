<?php

namespace Ecommage\CustomCatalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Amasty\Shopby\Model\Source\DisplayMode;

/**
 * Class FilterPrice
 *
 * @package Ecommage\CustomCatalog\Helper
 */
class FilterPrice extends AbstractHelper
{
    /**
     * @var null
     */
    protected $itemChange = null;
    /**
     * @param $filterCode
     * @param $filterItems
     *
     * @return array
     */
    public function getFilterItems($filterCode, $filterItems)
    {
        if ($filterCode !== DisplayMode::ATTRUBUTE_PRICE) {
            return $filterItems;
        }

        $index = count($filterItems) - 1;
        /** @var \Amasty\Shopby\Model\Layer\Filter\Item $filterItem */
        $filterItem = $filterItems[$index] ?? null;
        if ($filterItem) {
            $this->changeLabel($filterItem);
        }

        return $filterItems;
    }

    /**
     * @param $filters
     *
     * @return array
     */
    public function getActiveFilters($filters)
    {
        if (empty($filters)) {
            return $filters;
        }

        /** @var \Magento\Catalog\Model\Layer\Filter\Item $filter */
        foreach ($filters as $filter) {
            if ($filter->getFilter()->getRequestVar() !== DisplayMode::ATTRUBUTE_PRICE) {
                continue;
            }

            if (!$this->itemChange) {
                $this->getFilterItems(DisplayMode::ATTRUBUTE_PRICE, $filter->getFilter()->getItems());
            }

            if (!$this->itemChange) {
                continue;
            }

            $filterValue = str_replace(['-',','], ['_','_'], $filter->getValueString());
            $changeValue = str_replace(['-',','], ['_','_'], $this->itemChange->getValueString());
            if ($filterValue != $changeValue) {
                continue;
            }

            $this->changeLabel($filter);
        }
        return $filters;
    }

    /**
     * @param $filter
     *
     * @return mixed
     */
    private function changeLabel($filter)
    {
        try {
            $optionLabel = $filter->getOptionLabel();
            if ($optionLabel instanceof \Magento\Framework\Phrase) {
                $this->itemChange = $filter;
                [$from] = $optionLabel->getArguments();
                $filter->setData('label', __("%1 and above", $from));
                //$filterItem->setOptionLabel(__("%1 and above", $from));
            }
        } catch (\Exception $exception) {
            $this->_logger->debug($exception->getTraceAsString());
        }

        return $filter;
    }
}

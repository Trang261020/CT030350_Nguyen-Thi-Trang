<?php

namespace Ecommage\CustomCatalog\Plugin;

use Amasty\Rules\Model\Registry;
use Amasty\Shopby\Model\Layer\Filter\Item;
use Amasty\Shopby\Model\Source\DisplayMode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class FilterItem extends FulltextCollection
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * FilterItem constructor.
     *
     * @param Registry               $registry
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
        parent::__construct($scopeConfig, $registry);
    }

    /**
     * @return string
     */
    public function beforeGetOptionLabel(
        Item $filter
    ) {
        if ($this->isApplyPlugin()) {
            $label = $this->generateValueLabel($filter);
            $filter->setData('label', $label);
        }
    }

    /**
     * @param $price
     *
     * @return float
     */
    private function getPriceFromString($price)
    {
        return (float)preg_replace("/[^0-9.]/", "", strip_tags($price));
    }

    /**
     * @param float|string $fromPrice
     * @param float|string $toPrice
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return string|Phrase
     */
    private function renderRangeLabel($fromPrice, $toPrice)
    {
        $fromPrice = $this->getPriceFromString($fromPrice);
        $toPrice   = $this->getPriceFromString($toPrice);
        $maxKey    = $this->getFinalPriceFilter();
        if (
            sprintf('%s_%s', $fromPrice, $toPrice) == $maxKey ||
            (floatval($toPrice)) >= 999999 ||
            $toPrice == ''
        ) {
            $fromPrice = $this->priceCurrency->format($fromPrice);
            return __("%1 and above", $fromPrice);
        } else {
            $toPrice = ((float)$toPrice - 0.01);
            $toPrice = $this->priceCurrency->format($toPrice);
            return __('%1 - %2', $fromPrice, $toPrice);
        }
    }

    /**
     * @param \Amasty\Shopby\Model\Layer\Filter\Item $filterItem
     * @param $currencyRate
     *
     * @return Phrase
     */
    private function generateValueLabel($filterItem)
    {
        if ($filterItem->getFilter()->getRequestVar() == DisplayMode::ATTRUBUTE_PRICE) {
           $currentKey = str_replace(['-',','], ['_','_'], $filterItem->getValueString());
            $finalPriceFilter = $this->getFinalPriceFilter();
            if ($currentKey == $finalPriceFilter) {
                list($from, $to) = explode('_', $finalPriceFilter);
                return $this->renderRangeLabel($from, $to);
            }
        }

        return $filterItem->getLabel();
    }
}

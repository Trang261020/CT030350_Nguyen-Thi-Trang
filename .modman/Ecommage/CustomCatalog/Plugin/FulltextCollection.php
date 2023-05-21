<?php

namespace Ecommage\CustomCatalog\Plugin;

use Amasty\Rules\Model\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class FulltextCollection
 */
class FulltextCollection
{
    const FINAL_PRICE_FILTER = 'final_price_filter';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * FulltextCollection constructor.
     *
     * @param Registry $registry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Registry $registry
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->registry    = $registry;
    }

    /**
     * @return string|null
     */
    public function getFinalPriceFilter()
    {
        return $this->registry->registry(self::FINAL_PRICE_FILTER);
    }

    /**
     * @param $maxKey
     *
     * @return Registry
     */
    public function setFinalPriceFilter($maxKey)
    {
        return $this->registry->register(self::FINAL_PRICE_FILTER, $maxKey);
    }

    /**
     * @return bool
     */
    public function isApplyPlugin()
    {
        $priceRange = $this->scopeConfig->getValue('catalog/layered_navigation/price_range_calculation');
        if ($priceRange == 'auto') {
            return true;
        }

        return false;
    }

    /**
     * @param $subject
     * @param $facets
     *
     * @return mixed
     */
    public function afterGetFacetedData($subject, $facets)
    {
        if (!$this->isApplyPlugin()) {
            return $facets;
        }

        $maxKey = $maxTo = 0;
        foreach ($facets as $key => $aggregation) {
            if (strpos($key, '_') === false) {
                continue;
            }

            $to = (float)$aggregation['to'] ?? 0;
            if ($maxTo < $to) {
                $maxTo  = $to;
                $maxKey = $key;
            }
        }

        if (!empty($facets[$maxKey])) {
            $this->setFinalPriceFilter($maxKey);
            $aggregation     = $facets[$maxKey];
            $newKey          = substr($maxKey, 0, strrpos($maxKey, '_') + 1) . '*';
            $facets[$newKey] = $aggregation;
            unset($facets[$maxKey]);
        }

        return $facets;
    }
}

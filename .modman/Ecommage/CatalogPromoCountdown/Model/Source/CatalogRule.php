<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_PromoBanners
 */

namespace Ecommage\CatalogPromoCountdown\Model\Source;

use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CatalogRule
 * @package Ecommage\CatalogPromoCountdown\Model\Source
 */
class CatalogRule implements ArrayInterface
{
    /**
     * @var RuleFactory
     */
    protected $_catalogRuleFactory;

    /**
     * CatalogRule constructor.
     * @param RuleFactory $catalogRule
     */
    public function __construct(
        RuleFactory $catalogRuleFactory
    ) {
        $this->_catalogRuleFactory = $catalogRuleFactory;
    }

    /**
     * @return array|mixed
     */
    public function toOptionArray()
    {
        $options[] = ['value' => '', 'label' => 'Please choose rule'];
        /** @var Rule  $collection */
        $catalogRuleFactory = $this->_catalogRuleFactory->create();
        $collection = $catalogRuleFactory->getCollection();
        if (!empty($collection)) {
            foreach ($collection as $catalogRule) {
                $options[] = [
                    'value' => $catalogRule['rule_id'],
                    'label' => $catalogRule['name']
                ];
            }
        }

        return $options;
    }
}

<?php
/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecommage\BrandWidget\Block\Widget;

/**
 * Catalog Products List widget block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class BrandSlider extends \Amasty\ShopbyBrand\Block\Widget\BrandSlider
{
    /**
     * @return array
     */
    public function getbrandIds()
    {
        $brandIds = $this->getData('brand_ids');
        if (empty($brandIds)) {
            return [];
        }

        if (strpos($brandIds, ',') !== false) {
            return explode(',', $brandIds);
        }

        return (array)$brandIds;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $items = parent::getItems();
        $brandIds = $this->getbrandIds();
        $this->items = [];
        foreach ($items as $item) {
            if (in_array($item['brand_id'], $brandIds)) {
                $this->items[] = $item;
            }
        }

        return $this->items;
    }

    /**
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $parts = parent::getCacheKeyInfo();
        $parts['brand_ids'] = $this->getData('brand_ids');
        return $parts;
    }
}
